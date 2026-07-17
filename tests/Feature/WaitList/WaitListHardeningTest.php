<?php

namespace Tests\Feature\WaitList;

use App\Enums\WaitList\WaitListAlertDisposition;
use App\Enums\WaitList\WaitListAlertStatus;
use App\Enums\WaitList\WaitListRequestType;
use App\Enums\WaitList\WaitListStatus;
use App\Enums\WaitList\WaitListStorePreference;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Orders\Order;
use App\Models\ProductManagement\Product;
use App\Models\ProductManagement\ProductCategory;
use App\Models\WaitList\EquipmentWaitList;
use App\Models\WaitList\EquipmentWaitListAlert;
use App\Services\Equipment\EquipmentStatusService;
use App\Services\FirebaseService;
use App\Services\WaitList\WaitListMatcher;
use App\Services\WaitList\WaitListPushService;
use App\Services\WaitList\WaitListStats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Final hardening pass:
 *  1. Customer Accepted opportunities stay visible until conversion.
 *  2. Canonical counts distinguish contact OPPORTUNITIES (distinct records)
 *     from raw match ALERTS.
 *  3. Push delivery is never load-bearing — the database alert is canonical.
 */
class WaitListHardeningTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Customer $customer;
    private ProductCategory $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // A weekday inside business hours so pushes are attempted immediately.
        Carbon::setTestNow(Carbon::parse('2026-07-15 10:00:00'));

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

        $this->category = ProductCategory::create(['title' => 'Excavators', 'status' => 'Published', 'sort_order' => 1]);

        $this->product = Product::create([
            'unique_id'    => Str::uuid()->toString(),
            'product_name' => 'Mini Excavator 3.5T',
            'slug'         => 'mini-excavator-3-5t',
            'product_type' => 'Rental',
            'status'       => 'Published',
        ]);
        $this->product->categories()->sync([$this->category->id]);

        $this->instance(FirebaseService::class, $this->createMock(FirebaseService::class));
    }

    private function makeRecord(array $overrides = [], array $unitIds = []): EquipmentWaitList
    {
        $waitList = EquipmentWaitList::create(array_merge([
            'customer_id'         => $this->customer->id,
            'customer_name'       => 'Mike Harrison',
            'company_name'        => 'Harrison Grading LLC',
            'phone'               => '555-0100',
            'request_type'        => WaitListRequestType::Unified->value,
            'product_category_id' => $this->category->id,
            'store_preference'    => WaitListStorePreference::AnyStore->value,
            'created_by'          => $this->admin->id,
        ], $overrides));

        // Canonical unit-level selection: the record names exact assets
        foreach ($unitIds as $id) {
            $waitList->items()->create(['equipment_id' => $id]);
        }

        return $waitList;
    }

    private function makeUnit(string $suffix): Equipment
    {
        return Equipment::create([
            'unique_id' => "test-unit-$suffix", 'equipment_name' => "Mini Excavator #$suffix",
            'equipment_id' => "EX-$suffix", 'brand' => 'Test',
            'product_category_id' => $this->category->id,
            'assigned_product_id' => $this->product->id,
            'current_status' => 'rented',
        ]);
    }

    private function returnUnit(Equipment $unit): void
    {
        EquipmentStatusService::markReturnedToMaintenance($unit, 1, 1, null, $this->admin->id);
    }

    // ── 1. Customer Accepted stays visible until conversion ───────────────────

    public function test_accepted_record_remains_in_the_active_queue_until_converted(): void
    {
        $unit = $this->makeUnit('1');
        $waitList = $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerAccepted, $this->admin->id);

        // Still live demand: in the waiting scope, the urgency queue, and the
        // dedicated accepted-awaiting-conversion scope
        $this->assertContains($waitList->id, EquipmentWaitList::waiting()->pluck('id')->all());
        $this->assertContains($waitList->id, EquipmentWaitList::waiting()->byUrgency()->pluck('id')->all());
        $this->assertSame([$waitList->id], EquipmentWaitList::acceptedAwaitingConversion()->pluck('id')->all());
        $this->assertSame(1, WaitListStats::acceptedAwaitingConversion());

        // Visible with its distinct state on the default index view AND the record page
        $this->get(route('admin.wait-list.index'))->assertOk()->assertSee('Accepted — Awaiting Conversion');
        $this->get(route('admin.wait-list.show', $waitList))->assertOk()->assertSee('Accepted — Awaiting Conversion');

        // Only a real conversion (or explicit closure) removes it
        $order = Order::create(['order_number' => 'ORD-HARD-1', 'customer_name' => 'Mike Harrison']);
        $this->post(route('admin.wait-list.convert', $waitList), ['converted_order_id' => $order->id])->assertRedirect();

        $this->assertSame(WaitListStatus::Converted, $waitList->fresh()->status);
        $this->assertSame(0, WaitListStats::acceptedAwaitingConversion());
        $this->get(route('admin.wait-list.index'))->assertOk()->assertDontSee('Accepted — Awaiting Conversion');
    }

    public function test_accepted_record_can_still_be_explicitly_cancelled(): void
    {
        $unit = $this->makeUnit('1');
        $waitList = $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);
        EquipmentWaitListAlert::firstOrFail()->dispose(WaitListAlertDisposition::CustomerAccepted, $this->admin->id);

        $this->post(route('admin.wait-list.cancel', $waitList))->assertRedirect();

        $this->assertSame(WaitListStatus::Cancelled, $waitList->fresh()->status);
        $this->assertSame(0, WaitListStats::acceptedAwaitingConversion());
    }

    // ── 2. Canonical counts: opportunities vs raw alerts ──────────────────────

    public function test_one_record_matched_by_three_units_is_one_contact_opportunity(): void
    {
        $units = collect(['1', '2', '3'])->map(fn ($s) => $this->makeUnit($s));
        $this->makeRecord([], $units->pluck('id')->all());

        $units->each(fn ($unit) => $this->returnUnit($unit));

        $this->assertSame(3, WaitListStats::openMatchAlerts());
        $this->assertSame(1, WaitListStats::contactOpportunities());
        $this->assertSame(1, WaitListStats::newOpportunitiesToday());
    }

    public function test_three_records_matched_by_one_unit_are_three_contact_opportunities(): void
    {
        $unit = $this->makeUnit('1');
        foreach (range(1, 3) as $i) {
            $this->makeRecord([], [$unit->id]);
        }

        $this->returnUnit($unit);

        $this->assertSame(3, WaitListStats::openMatchAlerts());
        $this->assertSame(3, WaitListStats::contactOpportunities());
    }

    public function test_contacted_no_answer_remains_an_actionable_opportunity(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::ContactedNoAnswer, $this->admin->id);

        $this->assertSame(1, WaitListStats::contactOpportunities());
        $this->assertSame(1, WaitListStats::openMatchAlerts());
    }

    public function test_keep_waiting_removes_the_match_from_the_count_but_keeps_the_request_active(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::KeepWaiting, $this->admin->id);

        $this->assertSame(0, WaitListStats::contactOpportunities());
        $this->assertSame(0, WaitListStats::openMatchAlerts());
        $this->assertSame(1, WaitListStats::activeWaitingRecords());
    }

    public function test_terminal_dispositions_do_not_count_as_opportunities(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);

        EquipmentWaitListAlert::firstOrFail()
            ->dispose(WaitListAlertDisposition::CustomerNoLongerNeeds, $this->admin->id);

        $this->assertSame(0, WaitListStats::contactOpportunities());
        $this->assertSame(0, WaitListStats::activeWaitingRecords()); // request cancelled
    }

    public function test_duplicate_return_processing_never_inflates_any_count(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);

        WaitListMatcher::evaluateReturn($unit->fresh());
        WaitListMatcher::evaluateReturn($unit->fresh());

        $this->assertSame(1, WaitListStats::openMatchAlerts());
        $this->assertSame(1, WaitListStats::contactOpportunities());
        $this->assertSame(1, WaitListStats::newOpportunitiesToday());
    }

    // ── 3. Push failures are never load-bearing ────────────────────────────────

    public function test_firebase_delivery_failure_keeps_the_alert_and_the_return_intact(): void
    {
        $firebase = $this->createMock(FirebaseService::class);
        $firebase->method('sendToAllDevices')->willThrowException(new \RuntimeException('FCM unavailable'));
        $this->instance(FirebaseService::class, $firebase);

        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);

        $this->returnUnit($unit); // must not throw

        // The return completed and the canonical alert exists
        $this->assertSame('maintenance', $unit->fresh()->current_status->value);
        $this->assertSame(1, WaitListStats::openMatchAlerts());

        // Retry after the failure creates no duplicates
        WaitListMatcher::evaluateReturn($unit->fresh());
        $this->assertSame(1, WaitListStats::openMatchAlerts());
    }

    public function test_push_layer_construction_failure_is_isolated_from_matching(): void
    {
        // Simulates the local/dev condition where FirebaseService cannot even
        // be constructed (missing configuration).
        $this->app->bind(WaitListPushService::class, function () {
            throw new \RuntimeException('Firebase project not configured');
        });

        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);

        $this->returnUnit($unit); // return checklist must not break

        $alerts = WaitListMatcher::evaluateReturn($unit->fresh()); // direct call must not throw either

        $this->assertSame('maintenance', $unit->fresh()->current_status->value);
        $this->assertSame(1, WaitListStats::openMatchAlerts());
        $this->assertCount(0, $alerts); // deduped — the alert from the return stands alone
    }

    public function test_disposition_effects_are_atomic(): void
    {
        $unit = $this->makeUnit('1');
        $this->makeRecord([], [$unit->id]);
        $this->returnUnit($unit);
        $alert = EquipmentWaitListAlert::firstOrFail();

        $alert->dispose(WaitListAlertDisposition::CustomerAccepted, $this->admin->id);

        // All three effects landed together: alert resolved with disposition,
        // communication logged, parent transitioned
        $alert->refresh();
        $this->assertSame(WaitListAlertStatus::Dismissed, $alert->status);
        $this->assertSame(WaitListAlertDisposition::CustomerAccepted, $alert->disposition);
        $this->assertDatabaseHas('equipment_wait_list_communications', ['type' => 'customer_accepted']);
        $this->assertSame(WaitListStatus::Acknowledged, $alert->waitList->fresh()->status);
    }
}
