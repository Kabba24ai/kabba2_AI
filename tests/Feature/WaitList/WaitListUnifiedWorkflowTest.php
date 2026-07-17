<?php

namespace Tests\Feature\WaitList;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Enums\WaitList\WaitListAlertStatus;
use App\Enums\WaitList\WaitListMatchType;
use App\Enums\WaitList\WaitListReason;
use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStatus;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use App\Services\Equipment\EquipmentStatusService;
use App\Services\FirebaseService;
use App\Services\WaitList\WaitListMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Unified Wait List workflow: one record = one customer + one category +
 * one or more selected acceptable products. The customer return checklist
 * (EquipmentStatusService markReturned* → WaitListMatcher) is the match
 * trigger; matches are contact opportunities, never reservations, and each
 * one is disposed independently of the customer's overall request.
 */
class WaitListUnifiedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductCategory $category;
    private Product $productA;
    private Product $productB;
    private Equipment $unitA;
    private Store $storeOne;
    private Store $storeTwo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::create([
            'unique_id' => 'test-admin', 'employee_code' => '01',
            'first_name' => 'Admin', 'last_name' => 'User',
            'email' => 'admin@test.local', 'status' => 'Active',
        ]);
        $this->actingAs($this->admin);

        $this->customer = Customer::create([
            'first_name' => 'Mike', 'last_name' => 'Harrison',
            'company_name' => 'Harrison Grading LLC',
            'phone' => '555-0100', 'email' => 'mike@harrisongrading.test',
            'status' => 'Active',
        ]);

        $this->storeOne = Store::create(['store_name' => 'North Store', 'status' => 'Active']);
        $this->storeTwo = Store::create(['store_name' => 'South Store', 'status' => 'Active']);

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);

        $this->productA = $this->makeProduct('Mini Excavator 3.5T', [$this->category->id]);
        $this->productB = $this->makeProduct('Midi Excavator 6T', [$this->category->id]);

        $this->unitA = Equipment::create([
            'unique_id' => 'test-unit-a', 'equipment_name' => 'Mini Excavator 3.5T #1',
            'equipment_id' => 'EX-100', 'brand' => 'Test',
            'product_category_id' => $this->category->id,
            'assigned_product_id' => $this->productA->id,
            'store_id' => $this->storeOne->id,
            'current_status' => 'rented',
        ]);

        $this->instance(FirebaseService::class, $this->createMock(FirebaseService::class));
    }

    private function makeProduct(string $name, array $categoryIds): Product
    {
        $product = Product::create([
            'unique_id'    => Str::uuid()->toString(),
            'product_name' => $name,
            'slug'         => Str::slug($name) . '-' . Str::lower(Str::random(4)),
            'product_type' => 'Rental',
            'status'       => 'Published',
        ]);
        $product->categories()->sync($categoryIds);

        return $product;
    }

    private function createPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id'         => $this->customer->id,
            'product_category_id' => $this->category->id,
            'product_ids'         => [$this->productA->id],
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => WaitListReason::EquipmentFullyBooked->value,
        ], $overrides);
    }

    private function makeUnifiedRecord(array $productIds = null, array $overrides = []): EquipmentWaitList
    {
        $waitList = EquipmentWaitList::create(array_merge([
            'customer_id'         => $this->customer->id,
            'customer_name'       => 'Mike Harrison',
            'company_name'        => 'Harrison Grading LLC',
            'phone'               => '555-0100',
            'email'               => 'mike@harrisongrading.test',
            'request_type'        => WaitListRequestType::Unified->value,
            'product_category_id' => $this->category->id,
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => 'Equipment fully booked',
            'created_by'          => $this->admin->id,
        ], $overrides));

        $waitList->selectedProducts()->attach($productIds ?? [$this->productA->id]);

        return $waitList;
    }

    private function completeReturn(Equipment $equipment, bool $damaged = false): void
    {
        $damaged
            ? EquipmentStatusService::markReturnedDamaged($equipment, 1, 1, null, $this->admin->id)
            : EquipmentStatusService::markReturnedToMaintenance($equipment, 1, 1, null, $this->admin->id);
    }

    // ── Creation (scenarios 1, 2, 4, 5) ────────────────────────────────────────

    public function test_creating_a_record_with_one_selected_product(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload())->assertRedirect();

        $waitList = EquipmentWaitList::firstOrFail();
        $this->assertSame(WaitListRequestType::Unified, $waitList->request_type);
        $this->assertSame($this->category->id, $waitList->product_category_id);
        $this->assertSame([$this->productA->id], $waitList->selectedProducts()->pluck('products.id')->all());
    }

    public function test_creating_a_record_with_multiple_selected_products(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload([
            'product_ids' => [$this->productA->id, $this->productB->id],
        ]))->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$this->productA->id, $this->productB->id],
            EquipmentWaitList::firstOrFail()->selectedProducts()->pluck('products.id')->all(),
        );
    }

    public function test_save_is_prevented_when_no_product_is_selected(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload(['product_ids' => []]))
            ->assertSessionHasErrors('product_ids');

        $this->post(route('admin.wait-list.store'), collect($this->createPayload())->except('product_ids')->all())
            ->assertSessionHasErrors('product_ids');

        $this->assertDatabaseCount('equipment_wait_lists', 0);
    }

    public function test_products_from_other_categories_cannot_be_submitted(): void
    {
        $otherCategory = ProductCategory::create(['title' => 'Skid Steers', 'status' => 'Published', 'sort_order' => 2]);
        $skid = $this->makeProduct('Skid Steer S70', [$otherCategory->id]);

        // A foreign product hidden among valid ones is rejected server-side
        $this->post(route('admin.wait-list.store'), $this->createPayload([
            'product_ids' => [$this->productA->id, $skid->id],
        ]))->assertSessionHasErrors('product_ids');

        $this->assertDatabaseCount('equipment_wait_lists', 0);
    }

    // ── Legacy migration (scenarios 7, 8) ──────────────────────────────────────

    private function runUnificationMigration(): void
    {
        $migration = include base_path('database/migrations/2026_07_16_120000_unify_equipment_wait_lists.php');
        $migration->up(); // idempotent — schema guarded, data rule re-applied
    }

    public function test_legacy_category_records_migrate_to_the_categorys_rental_products(): void
    {
        $legacy = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);

        $this->runUnificationMigration();

        $this->assertEqualsCanonicalizing(
            [$this->productA->id, $this->productB->id],
            $legacy->selectedProducts()->pluck('products.id')->all(),
        );
        // Historical truth is preserved — the original type is not rewritten
        $this->assertSame(WaitListRequestType::Category, $legacy->fresh()->request_type);
    }

    public function test_legacy_specific_equipment_records_migrate_to_their_units_products(): void
    {
        $legacy = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::SpecificEquipment->value,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);
        $legacy->items()->create(['equipment_id' => $this->unitA->id]);

        // A unit with NO assigned product on a second record → safe fallback
        $orphanUnit = Equipment::create([
            'unique_id' => 'test-orphan', 'equipment_name' => 'Orphan Unit',
            'equipment_id' => 'OR-1', 'brand' => 'Test',
        ]);
        $unmigratable = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::SpecificEquipment->value,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);
        $unmigratable->items()->create(['equipment_id' => $orphanUnit->id]);

        $this->runUnificationMigration();

        // The unit's product became the selected product; the category was
        // adopted from the unit
        $this->assertSame([$this->productA->id], $legacy->selectedProducts()->pluck('products.id')->all());
        $this->assertSame($this->category->id, $legacy->fresh()->product_category_id);

        // The ambiguous record got NO guessed products — its items remain and
        // the matcher's exact-equipment fallback still honors it
        $this->assertSame([], $unmigratable->selectedProducts()->pluck('products.id')->all());
        $this->assertDatabaseHas('equipment_wait_list_items', ['equipment_id' => $orphanUnit->id]);

        EquipmentStatusService::markReturnedToMaintenance($orphanUnit, 1, 1, null, $this->admin->id);
        $this->assertSame(
            WaitListMatchType::ExactEquipment,
            EquipmentWaitListAlert::where('equipment_wait_list_id', $unmigratable->id)->firstOrFail()->match_type,
        );
    }

    // ── Matching (scenarios 9–14) ──────────────────────────────────────────────

    public function test_a_returned_matching_unit_creates_a_product_match(): void
    {
        $waitList = $this->makeUnifiedRecord();

        $this->completeReturn($this->unitA);

        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame($waitList->id, $alert->equipment_wait_list_id);
        $this->assertSame(WaitListMatchType::Product, $alert->match_type);
        $this->assertSame($this->productA->id, $alert->matched_product_id);
        $this->assertSame($this->category->id, $alert->matched_category_id);
    }

    public function test_an_unrelated_product_does_not_create_a_match(): void
    {
        $this->makeUnifiedRecord([$this->productB->id]); // acceptable: Midi only

        $this->completeReturn($this->unitA); // returned unit is a Mini

        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);
    }

    public function test_store_preference_is_respected(): void
    {
        // Wants a unit at South Store specifically; the return lands at North
        $this->makeUnifiedRecord(null, [
            'store_preference' => WaitListStorePreference::SpecificStore->value,
            'store_id'         => $this->storeTwo->id,
        ]);

        $this->completeReturn($this->unitA);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);

        // Same preference at the unit's actual store → matches
        $this->makeUnifiedRecord(null, [
            'store_preference' => WaitListStorePreference::SpecificStore->value,
            'store_id'         => $this->storeOne->id,
        ]);

        $this->unitA->update(['current_status' => 'rented']);
        $this->completeReturn($this->unitA);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);
    }

    public function test_maintenance_hold_unit_generates_a_clearly_labeled_potential_match(): void
    {
        $this->makeUnifiedRecord();

        $this->completeReturn($this->unitA); // → maintenance

        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame('maintenance', $alert->equipment_status_at_match);

        // The Wait List page shows the REAL status, never "available"
        $html = $this->get(route('admin.wait-list.alerts'))->assertOk()->getContent();
        $this->assertStringContainsString('not immediately available', $html);
    }

    public function test_damaged_unit_generates_a_clearly_labeled_potential_match(): void
    {
        $this->makeUnifiedRecord();

        $this->completeReturn($this->unitA, damaged: true);

        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame('damaged', $alert->equipment_status_at_match);
        $this->assertSame(WaitListMatchType::Product, $alert->match_type);
    }

    public function test_one_returned_unit_can_match_multiple_wait_list_records(): void
    {
        $first  = $this->makeUnifiedRecord();
        $second = $this->makeUnifiedRecord([$this->productA->id, $this->productB->id]);

        $this->completeReturn($this->unitA);

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            EquipmentWaitListAlert::pluck('equipment_wait_list_id')->all(),
        );
    }

    public function test_a_rented_unit_never_matches(): void
    {
        $this->makeUnifiedRecord();

        // Defensive guard: direct evaluation of a still-rented unit is a no-op
        WaitListMatcher::evaluateReturn($this->unitA->fresh());

        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);
    }

    // ── Idempotency (scenario 15) ──────────────────────────────────────────────

    public function test_reprocessing_the_same_return_event_creates_no_duplicate_alerts(): void
    {
        $this->makeUnifiedRecord();

        $this->completeReturn($this->unitA);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        // Same event re-evaluated (double submit / retry) → nothing new
        WaitListMatcher::evaluateReturn($this->unitA->fresh());
        WaitListMatcher::evaluateReturn($this->unitA->fresh());
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        // Even after the match is resolved, the SAME event stays deduplicated
        EquipmentWaitListAlert::first()->dispose(WaitListAlertDisposition::KeepWaiting, $this->admin->id);
        WaitListMatcher::evaluateReturn($this->unitA->fresh());
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        // A genuinely NEW return of the same unit alerts again
        $this->unitA->update(['current_status' => 'rented']);
        $this->travel(1)->hours();
        $this->completeReturn($this->unitA);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 2);
    }

    // ── Disposition lifecycle (scenarios 16, 17) ───────────────────────────────

    public function test_keep_waiting_resolves_the_match_but_not_the_request(): void
    {
        $waitList = $this->makeUnifiedRecord();
        $this->completeReturn($this->unitA);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), [
            'disposition' => WaitListAlertDisposition::KeepWaiting->value,
        ])->assertRedirect();

        $alert->refresh();
        $this->assertSame(WaitListAlertStatus::Dismissed, $alert->status);
        $this->assertSame(WaitListAlertDisposition::KeepWaiting, $alert->disposition);

        // The customer's overall request is still live demand
        $this->assertContains($waitList->fresh()->status->value, WaitListStatus::waiting());
    }

    public function test_customer_accepted_resolves_the_match_and_hands_off_to_convert(): void
    {
        $waitList = $this->makeUnifiedRecord();
        $this->completeReturn($this->unitA);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), [
            'disposition' => WaitListAlertDisposition::CustomerAccepted->value,
        ])->assertRedirect();

        $alert->refresh();
        $this->assertSame(WaitListAlertStatus::Dismissed, $alert->status);
        $this->assertSame(WaitListAlertDisposition::CustomerAccepted, $alert->disposition);

        // Nothing is reserved or converted automatically — the record moves to
        // Acknowledged and staff close it through the existing Convert flow
        $this->assertSame(WaitListStatus::Acknowledged, $waitList->fresh()->status);

        $order = \App\Models\Orders\Order::create(['order_number' => 'ORD-TEST-1', 'customer_name' => 'Mike Harrison']);
        $this->post(route('admin.wait-list.convert', $waitList), ['converted_order_id' => $order->id])
            ->assertRedirect();
        $this->assertSame(WaitListStatus::Converted, $waitList->fresh()->status);
    }

    public function test_customer_no_longer_needs_equipment_closes_the_request(): void
    {
        $waitList = $this->makeUnifiedRecord();
        $this->completeReturn($this->unitA);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerNoLongerNeeds, $this->admin->id);

        $this->assertSame(WaitListStatus::Cancelled, $waitList->fresh()->status);
    }

    public function test_contacted_no_answer_keeps_the_alert_actionable(): void
    {
        $this->makeUnifiedRecord();
        $this->completeReturn($this->unitA);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $alert->dispose(WaitListAlertDisposition::ContactedNoAnswer, $this->admin->id);

        $alert->refresh();
        $this->assertSame(WaitListAlertStatus::Acknowledged, $alert->status);
        $this->assertSame(1, EquipmentWaitListAlert::open()->count());

        // Every disposition leaves a communication-history trace
        $this->assertDatabaseHas('equipment_wait_list_communications', [
            'type' => 'no_answer',
        ]);
    }

    public function test_invalid_dispositions_are_rejected(): void
    {
        $this->makeUnifiedRecord();
        $this->completeReturn($this->unitA);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), ['disposition' => 'ghosted'])
            ->assertSessionHasErrors('disposition');

        $this->assertSame(WaitListAlertStatus::Unacknowledged, $alert->fresh()->status);
    }
}
