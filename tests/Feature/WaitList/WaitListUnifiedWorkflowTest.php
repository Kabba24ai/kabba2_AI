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
 * Unified Wait List workflow — UNIT-LEVEL selection: one record = one
 * customer + one category + one or more selected acceptable EQUIPMENT
 * INVENTORY UNITS (individual assets by Equipment ID, never catalog
 * products). Two units of the same product are two independent choices;
 * a returned unit matches only when that EXACT unit was selected.
 */
class WaitListUnifiedWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductCategory $category;
    private Product $product;
    private Equipment $unitOne;   // KUB-ME-1 — 3 Ton Kub U35
    private Equipment $unitSeven; // KUB-ME-7 — SAME product, different asset
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

        $this->product = Product::create([
            'unique_id' => Str::uuid()->toString(), 'product_name' => '3 Ton - Kub U35',
            'slug' => '3-ton-kub-u35', 'product_type' => 'Rental', 'status' => 'Published',
        ]);
        $this->product->categories()->sync([$this->category->id]);

        $this->unitOne   = $this->makeUnit('KUB-ME-1');
        $this->unitSeven = $this->makeUnit('KUB-ME-7');

        $this->instance(FirebaseService::class, $this->createMock(FirebaseService::class));
    }

    private function makeUnit(string $code, array $overrides = []): Equipment
    {
        return Equipment::create(array_merge([
            'unique_id' => 'u-' . Str::lower(Str::random(8)),
            'equipment_name' => '3 Ton - Kub U35',
            'equipment_id' => $code, 'brand' => 'Kubota',
            'product_category_id' => $this->category->id,
            'assigned_product_id' => $this->product->id,
            'store_id' => $this->storeOne->id,
            'current_status' => 'rented',
            'not_for_rent' => 0,
        ], $overrides));
    }

    private function createPayload(array $overrides = []): array
    {
        return array_merge([
            'customer_id'         => $this->customer->id,
            'product_category_id' => $this->category->id,
            'equipment_ids'       => [$this->unitOne->id],
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'reason'              => WaitListReason::EquipmentFullyBooked->value,
        ], $overrides);
    }

    private function makeUnifiedRecord(array $equipmentIds = null, array $overrides = []): EquipmentWaitList
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

        foreach ($equipmentIds ?? [$this->unitOne->id] as $id) {
            $waitList->items()->create(['equipment_id' => $id]);
        }

        return $waitList;
    }

    private function completeReturn(Equipment $equipment, bool $damaged = false): void
    {
        $damaged
            ? EquipmentStatusService::markReturnedDamaged($equipment, 1, 1, null, $this->admin->id)
            : EquipmentStatusService::markReturnedToMaintenance($equipment, 1, 1, null, $this->admin->id);
    }

    // ── Creation: units persist independently ──────────────────────────────────

    public function test_creating_a_record_with_one_selected_unit(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload())->assertRedirect();

        $waitList = EquipmentWaitList::firstOrFail();
        $this->assertSame(WaitListRequestType::Unified, $waitList->request_type);
        $this->assertSame($this->category->id, $waitList->product_category_id);
        // One selected unit persists as exactly one selected unit
        $this->assertSame([$this->unitOne->id], $waitList->items()->pluck('equipment_id')->all());
        // Nothing is silently converted into product selections
        $this->assertSame(0, $waitList->selectedProducts()->count());
    }

    public function test_multiple_selected_units_persist_independently(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload([
            'equipment_ids' => [$this->unitOne->id, $this->unitSeven->id],
        ]))->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$this->unitOne->id, $this->unitSeven->id],
            EquipmentWaitList::firstOrFail()->items()->pluck('equipment_id')->all(),
        );
    }

    public function test_selecting_one_unit_does_not_implicitly_select_its_product_twin(): void
    {
        // KUB-ME-1 selected; KUB-ME-7 (same product) was NOT
        $this->post(route('admin.wait-list.store'), $this->createPayload())->assertRedirect();

        $ids = EquipmentWaitList::firstOrFail()->items()->pluck('equipment_id')->all();
        $this->assertContains($this->unitOne->id, $ids);
        $this->assertNotContains($this->unitSeven->id, $ids);
    }

    public function test_save_is_prevented_when_no_unit_is_selected(): void
    {
        $this->post(route('admin.wait-list.store'), $this->createPayload(['equipment_ids' => []]))
            ->assertSessionHasErrors('equipment_ids');

        $this->post(route('admin.wait-list.store'), collect($this->createPayload())->except('equipment_ids')->all())
            ->assertSessionHasErrors('equipment_ids');

        $this->assertDatabaseCount('equipment_wait_lists', 0);
    }

    public function test_units_from_other_categories_cannot_be_submitted(): void
    {
        $otherCategory = ProductCategory::create(['title' => 'Skid Steers', 'status' => 'Published', 'sort_order' => 2]);
        $skidUnit = $this->makeUnit('SS-70', ['product_category_id' => $otherCategory->id, 'equipment_name' => 'Skid Steer S70']);

        // A foreign unit hidden among valid ones is rejected server-side
        $this->post(route('admin.wait-list.store'), $this->createPayload([
            'equipment_ids' => [$this->unitOne->id, $skidUnit->id],
        ]))->assertSessionHasErrors('equipment_ids');

        $this->assertDatabaseCount('equipment_wait_lists', 0);
    }

    public function test_no_upper_limit_on_selected_units(): void
    {
        $extra = collect(range(2, 6))->map(fn ($i) => $this->makeUnit("KUB-ME-$i"));

        $this->post(route('admin.wait-list.store'), $this->createPayload([
            'equipment_ids' => $extra->pluck('id')->push($this->unitOne->id)->push($this->unitSeven->id)->all(),
        ]))->assertRedirect();

        $this->assertSame(7, EquipmentWaitList::firstOrFail()->items()->count());
    }

    // ── Corrective migration (legacy + product-era handling) ───────────────────

    private function runCorrectiveMigration(): void
    {
        $migration = include base_path('database/migrations/2026_07_16_200000_correct_wait_list_selection_to_equipment_units.php');
        $migration->up(); // idempotent
    }

    public function test_legacy_exact_unit_selections_remain_exact_after_correction(): void
    {
        // A legacy specific-equipment record for KUB-ME-1 that the product
        // migration had broadened with a derived product row
        $legacy = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::SpecificEquipment->value,
            'product_category_id' => $this->category->id,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);
        $legacy->items()->create(['equipment_id' => $this->unitOne->id]);
        $legacy->selectedProducts()->attach([$this->product->id]); // the incorrect broadening

        $this->runCorrectiveMigration();

        // The original exact unit stands alone; the derived product row is gone
        $this->assertSame([$this->unitOne->id], $legacy->items()->pluck('equipment_id')->all());
        $this->assertSame(0, $legacy->selectedProducts()->count());

        // And KUB-ME-7 (same product) returning creates NO match
        $this->completeReturn($this->unitSeven);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);

        // While KUB-ME-1 returning does
        $this->completeReturn($this->unitOne);
        $this->assertSame(1, EquipmentWaitListAlert::count());
    }

    public function test_legacy_category_records_expand_to_a_frozen_unit_snapshot(): void
    {
        $legacy = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::Category->value,
            'product_category_id' => $this->category->id,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);
        $legacy->selectedProducts()->attach([$this->product->id]); // derived by the product migration

        $this->runCorrectiveMigration();

        // "Any unit in this category" → every current unit, frozen
        $this->assertEqualsCanonicalizing(
            [$this->unitOne->id, $this->unitSeven->id],
            $legacy->items()->pluck('equipment_id')->all(),
        );
        $this->assertSame(0, $legacy->selectedProducts()->count());

        // A unit added to the category LATER is not silently included
        $later = $this->makeUnit('KUB-ME-99');
        $this->runCorrectiveMigration(); // re-run: snapshot already frozen
        $this->assertNotContains($later->id, $legacy->items()->pluck('equipment_id')->all());
    }

    public function test_ambiguous_product_era_records_are_not_silently_expanded(): void
    {
        // Created through the short-lived product-based form: products only
        $productEra = EquipmentWaitList::create([
            'customer_id' => $this->customer->id, 'customer_name' => 'Mike Harrison',
            'request_type' => WaitListRequestType::Unified->value,
            'product_category_id' => $this->category->id,
            'store_preference' => WaitListStorePreference::AnyStore->value,
        ]);
        $productEra->selectedProducts()->attach([$this->product->id]);

        $this->runCorrectiveMigration();

        // No units were guessed; the product audit trail is preserved
        $this->assertSame(0, $productEra->items()->count());
        $this->assertSame([$this->product->id], $productEra->selectedProducts()->pluck('products.id')->all());

        // Its recorded product intent still matches until staff re-select units
        $this->completeReturn($this->unitOne);
        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame(WaitListMatchType::Product, $alert->match_type);
    }

    // ── Matching: exact selected unit only ─────────────────────────────────────

    public function test_returning_the_exact_selected_unit_creates_a_match(): void
    {
        $waitList = $this->makeUnifiedRecord([$this->unitOne->id]);

        $this->completeReturn($this->unitOne);

        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame($waitList->id, $alert->equipment_wait_list_id);
        $this->assertSame($this->unitOne->id, $alert->equipment_id);
        $this->assertSame(WaitListMatchType::ExactEquipment, $alert->match_type);
    }

    public function test_a_same_product_unselected_unit_does_not_match(): void
    {
        // Customer would accept KUB-ME-1 only; KUB-ME-7 is the same product
        $this->makeUnifiedRecord([$this->unitOne->id]);

        $this->completeReturn($this->unitSeven);

        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);
    }

    public function test_returning_one_of_several_selected_units_matches(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id, $this->unitSeven->id]);

        $this->completeReturn($this->unitSeven);

        $alert = EquipmentWaitListAlert::firstOrFail();
        $this->assertSame($this->unitSeven->id, $alert->equipment_id);
        $this->assertSame(WaitListMatchType::ExactEquipment, $alert->match_type);
    }

    public function test_store_preference_is_respected(): void
    {
        // Wants the unit at South Store specifically; the return lands at North
        $this->makeUnifiedRecord([$this->unitOne->id], [
            'store_preference' => WaitListStorePreference::SpecificStore->value,
            'store_id'         => $this->storeTwo->id,
        ]);

        $this->completeReturn($this->unitOne);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);

        // Same preference at the unit's actual store → matches
        $this->makeUnifiedRecord([$this->unitOne->id], [
            'store_preference' => WaitListStorePreference::SpecificStore->value,
            'store_id'         => $this->storeOne->id,
        ]);

        $this->unitOne->update(['current_status' => 'rented']);
        $this->completeReturn($this->unitOne);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);
    }

    public function test_maintenance_hold_and_damaged_selected_units_match_with_true_status(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id, $this->unitSeven->id]);

        $this->completeReturn($this->unitOne); // maintenance
        $this->completeReturn($this->unitSeven, damaged: true);

        $statuses = EquipmentWaitListAlert::pluck('equipment_status_at_match', 'equipment_id');
        $this->assertSame('maintenance', $statuses[$this->unitOne->id]);
        $this->assertSame('damaged', $statuses[$this->unitSeven->id]);

        $html = $this->get(route('admin.wait-list.alerts'))->assertOk()->getContent();
        $this->assertStringContainsString('not immediately available', $html);
        $this->assertStringNotContainsString('Rent Ready', $html);
    }

    public function test_one_returned_unit_can_match_multiple_records_that_selected_it(): void
    {
        $first  = $this->makeUnifiedRecord([$this->unitOne->id]);
        $second = $this->makeUnifiedRecord([$this->unitOne->id, $this->unitSeven->id]);
        $this->makeUnifiedRecord([$this->unitSeven->id]); // did NOT select unit one

        $this->completeReturn($this->unitOne);

        $this->assertEqualsCanonicalizing(
            [$first->id, $second->id],
            EquipmentWaitListAlert::pluck('equipment_wait_list_id')->all(),
        );
    }

    public function test_a_rented_unit_never_matches(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id]);

        WaitListMatcher::evaluateReturn($this->unitOne->fresh()); // still rented

        $this->assertDatabaseCount('equipment_wait_list_alerts', 0);
    }

    // ── Idempotency ────────────────────────────────────────────────────────────

    public function test_reprocessing_the_same_return_event_creates_no_duplicate_alerts(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id]);

        $this->completeReturn($this->unitOne);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        WaitListMatcher::evaluateReturn($this->unitOne->fresh());
        WaitListMatcher::evaluateReturn($this->unitOne->fresh());
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        EquipmentWaitListAlert::first()->dispose(WaitListAlertDisposition::KeepWaiting, $this->admin->id);
        WaitListMatcher::evaluateReturn($this->unitOne->fresh());
        $this->assertDatabaseCount('equipment_wait_list_alerts', 1);

        // A genuinely NEW return of the same unit alerts again
        $this->unitOne->update(['current_status' => 'rented']);
        $this->travel(1)->hours();
        $this->completeReturn($this->unitOne);
        $this->assertDatabaseCount('equipment_wait_list_alerts', 2);
    }

    // ── Disposition lifecycle ──────────────────────────────────────────────────

    public function test_keep_waiting_resolves_the_match_but_not_the_request(): void
    {
        $waitList = $this->makeUnifiedRecord([$this->unitOne->id]);
        $this->completeReturn($this->unitOne);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), [
            'disposition' => WaitListAlertDisposition::KeepWaiting->value,
        ])->assertRedirect();

        $alert->refresh();
        $this->assertSame(WaitListAlertStatus::Dismissed, $alert->status);
        $this->assertSame(WaitListAlertDisposition::KeepWaiting, $alert->disposition);
        $this->assertContains($waitList->fresh()->status->value, WaitListStatus::waiting());
    }

    public function test_customer_accepted_resolves_the_match_and_hands_off_to_convert(): void
    {
        $waitList = $this->makeUnifiedRecord([$this->unitOne->id]);
        $this->completeReturn($this->unitOne);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), [
            'disposition' => WaitListAlertDisposition::CustomerAccepted->value,
        ])->assertRedirect();

        $this->assertSame(WaitListStatus::Acknowledged, $waitList->fresh()->status);
        $this->assertSame([$waitList->id], EquipmentWaitList::acceptedAwaitingConversion()->pluck('id')->all());

        $order = \App\Models\Orders\Order::create(['order_number' => 'ORD-TEST-1', 'customer_name' => 'Mike Harrison']);
        $this->post(route('admin.wait-list.convert', $waitList), ['converted_order_id' => $order->id])
            ->assertRedirect();
        $this->assertSame(WaitListStatus::Converted, $waitList->fresh()->status);
    }

    public function test_customer_no_longer_needs_equipment_closes_the_request(): void
    {
        $waitList = $this->makeUnifiedRecord([$this->unitOne->id]);
        $this->completeReturn($this->unitOne);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerNoLongerNeeds, $this->admin->id);

        $this->assertSame(WaitListStatus::Cancelled, $waitList->fresh()->status);
    }

    public function test_contacted_no_answer_keeps_the_alert_actionable(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id]);
        $this->completeReturn($this->unitOne);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $alert->dispose(WaitListAlertDisposition::ContactedNoAnswer, $this->admin->id);

        $this->assertSame(WaitListAlertStatus::Acknowledged, $alert->fresh()->status);
        $this->assertSame(1, EquipmentWaitListAlert::open()->count());
        $this->assertDatabaseHas('equipment_wait_list_communications', ['type' => 'no_answer']);
    }

    public function test_invalid_dispositions_are_rejected(): void
    {
        $this->makeUnifiedRecord([$this->unitOne->id]);
        $this->completeReturn($this->unitOne);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $this->post(route('admin.wait-list.alerts.disposition', $alert), ['disposition' => 'ghosted'])
            ->assertSessionHasErrors('disposition');

        $this->assertSame(WaitListAlertStatus::Unacknowledged, $alert->fresh()->status);
    }
}
