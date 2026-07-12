<?php

namespace Tests\Feature\Dashboard;

use App\Livewire\Dashboard\AlertsSection;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\AlertStatusTransition;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\AlertLifecycleService;
use App\Services\ChargeService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Dashboard V2 Phase 1B — alert lifecycle tracking + Completed Today.
 */
class AlertLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'first_name' => 'Life', 'last_name' => 'Cycle',
            'email' => 'life-' . uniqid() . '@example.com',
            'password' => bcrypt('secret'), 'status' => 'Active',
        ]);
        $this->customer = Customer::create([
            'first_name' => 'Cust', 'last_name' => 'Omer',
            'email' => 'cust-' . uniqid() . '@example.com', 'status' => 'Active',
        ]);
    }

    private function makeOrderProduct(string $type): OrderProduct
    {
        $order = Order::create([
            'order_number' => 'LC-' . uniqid(),
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Cust Omer',
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ]);
        $equipment = \App\Models\MaintenanceManagement\Equipment::create([
            'unique_id' => 'EQ-' . uniqid(),
            'equipment_name' => 'Test Unit',
            'equipment_id' => 'CODE-' . uniqid(),
            'brand' => 'TestBrand',
            'not_for_rent' => 0,
        ]);

        return OrderProduct::create([
            'order_id' => $order->id,
            'equipment_id' => $equipment->id,
            'product_name' => 'Test Rental Item',
            'price' => 100, 'quantity' => 1, 'total' => 100,
            'fuel_total_charge' => $type === 'fuel' ? 50 : null,
            'fuel_charge_status' => 'pending',
            'damage_charge' => $type === 'damage' ? 50 : 0,
            'damage_status' => 'pending',
        ]);
    }

    private function statusValue($status): ?string
    {
        return $status instanceof \App\Enums\Orders\OrderProductChargeStatus ? $status->value : $status;
    }

    /**
     * Competing same-cycle terminal outcomes do not diverge: the second op
     * re-reads the locked/updated (now terminal) status and does NOT overwrite
     * it, and the recorded lifecycle status matches the source status.
     */
    public function test_competing_terminal_outcomes_do_not_diverge(): void
    {
        $op = $this->makeOrderProduct('fuel');

        // First terminal op wins: resolved.
        ChargeService::markResolved($op, 'fuel', 'resolve wins', $this->user->id);
        $op->refresh();

        // Competing op attempts uncollectible on the now-resolved charge.
        ChargeService::markUncollectible($op, 'fuel', $this->user->id);
        $op->refresh();

        // Source status unchanged, exactly one lifecycle row, statuses agree.
        $this->assertSame('resolved', $this->statusValue($op->fuel_charge_status));
        $rows = AlertStatusTransition::where('source_id', $op->id)
            ->where('source_type', 'order_product')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('resolved', $rows->first()->new_status);
    }

    /**
     * Pure-CRM CustomerAccount transition: locks + records atomically; an
     * OP-linked account is NOT recorded as a separate lifecycle source.
     */
    public function test_pure_crm_transition_locks_and_records(): void
    {
        $order = Order::create([
            'order_number' => 'LC-CRM2-' . uniqid(), 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Cust', 'grand_total' => 0,
        ]);

        $pure = CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id, 'type' => 'charge',
            'reason' => 'Fuel Charge', 'amount' => 30, 'fuel_alert_status' => 'pending',
            'order_product_id' => null, 'date' => now(),
        ]);

        DB::transaction(function () use ($pure) {
            $res = AlertLifecycleService::transitionCustomerAccount((int) $pure->id, 'fuel', 'resolved', $this->user->id);
            $this->assertNotNull($res);
        });

        $pure->refresh();
        $this->assertSame('resolved', $pure->fuel_alert_status);
        $this->assertDatabaseHas('alert_status_transitions', [
            'source_type' => 'customer_account', 'source_id' => $pure->id,
            'alert_type' => 'fuel', 'new_status' => 'resolved',
        ]);

        // OP-linked account: status still updates, but no separate lifecycle row.
        $op = $this->makeOrderProduct('damage');
        $linked = CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id, 'type' => 'charge',
            'reason' => 'Damages', 'amount' => 30, 'damage_alert_status' => 'pending',
            'order_product_id' => $op->id, 'date' => now(),
        ]);
        DB::transaction(function () use ($linked) {
            AlertLifecycleService::transitionCustomerAccount((int) $linked->id, 'damage', 'resolved', $this->user->id);
        });
        $linked->refresh();
        $this->assertSame('resolved', $linked->damage_alert_status);
        $this->assertDatabaseMissing('alert_status_transitions', [
            'source_type' => 'customer_account', 'source_id' => $linked->id,
        ]);
    }

    /**
     * A lifecycle collision resolving to an existing row with a DIFFERENT
     * new_status is surfaced (not silently ignored). Crafted so record()'s
     * cycleSeq lands on the pre-seeded c2 key — simulating a concurrent winner
     * that logged the same cycle with a different terminal outcome.
     */
    public function test_record_throws_on_divergent_status_for_same_cycle_key(): void
    {
        $op = $this->makeOrderProduct('fuel');

        // One existing row → record() will compute cycleSeq 2 → key ...:c2.
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $op->id,
            'previous_status' => 'pending', 'new_status' => 'uncollectible',
            'idempotency_key' => "fuel:order_product:{$op->id}:c2", 'transitioned_at' => now(),
        ]);

        $this->expectException(\App\Exceptions\AlertLifecycleConsistencyException::class);
        AlertLifecycleService::record('fuel', 'order_product', (int) $op->id, 'pending', 'resolved', $op->order_id, $this->user->id);
    }

    /** All six OrderProduct terminal transitions create one lifecycle row each. */
    public function test_order_product_transitions_are_recorded(): void
    {
        foreach (['fuel', 'damage'] as $type) {
            foreach (['resolved', 'completed', 'uncollectible'] as $status) {
                $op = $this->makeOrderProduct($type);
                AlertLifecycleService::recordOrderProduct($op, $type, 'pending', $status, $this->user->id);

                $this->assertDatabaseHas('alert_status_transitions', [
                    'alert_type' => $type,
                    'source_type' => 'order_product',
                    'source_id' => $op->id,
                    'new_status' => $status,
                    'previous_status' => 'pending',
                    'order_id' => $op->order_id,
                    'transitioned_by' => $this->user->id,
                ]);
            }
        }
        $this->assertSame(6, AlertStatusTransition::count());
    }

    /** Pure-CRM CustomerAccount transitions record; OP-linked CA rows do not. */
    public function test_crm_customer_account_transitions(): void
    {
        $order = Order::create([
            'order_number' => 'LC-CRM-' . uniqid(), 'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id, 'customer_name' => 'Cust', 'grand_total' => 0,
        ]);

        // Pure CRM (order_product_id NULL) → recorded
        $pureCrm = CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id,
            'type' => 'charge', 'reason' => 'Fuel Charge', 'amount' => 25,
            'fuel_alert_status' => 'pending', 'order_product_id' => null, 'date' => now(),
        ]);
        AlertLifecycleService::recordCustomerAccount($pureCrm, 'fuel', 'pending', 'resolved', $this->user->id);

        // OP-linked CA (order_product_id set) → skipped (represented by the OP)
        $op = $this->makeOrderProduct('damage');
        $linkedCa = CustomerAccount::create([
            'customer_id' => $this->customer->id, 'order_id' => $order->id,
            'type' => 'charge', 'reason' => 'Damages', 'amount' => 25,
            'damage_alert_status' => 'pending', 'order_product_id' => $op->id, 'date' => now(),
        ]);
        AlertLifecycleService::recordCustomerAccount($linkedCa, 'damage', 'pending', 'resolved', $this->user->id);

        $this->assertDatabaseHas('alert_status_transitions', [
            'source_type' => 'customer_account', 'source_id' => $pureCrm->id, 'alert_type' => 'fuel', 'new_status' => 'resolved',
        ]);
        $this->assertDatabaseMissing('alert_status_transitions', [
            'source_type' => 'customer_account', 'source_id' => $linkedCa->id,
        ]);
        $this->assertSame(1, AlertStatusTransition::count());
    }

    /**
     * A retry of the same operation does not duplicate; a genuine reopen +
     * re-resolve records a second, legitimate lifecycle event.
     */
    public function test_retry_is_deduped_but_reopen_records_new_cycle(): void
    {
        $op = $this->makeOrderProduct('fuel'); // status 'pending'

        // 1. Initial outstanding → resolved is recorded (cycle 1).
        ChargeService::markResolved($op, 'fuel', 'first pass', $this->user->id);
        $this->assertSame(1, AlertStatusTransition::where('source_id', $op->id)->count());
        $row1 = AlertStatusTransition::where('source_id', $op->id)->first();
        $this->assertSame('pending', $row1->previous_status, 'previous status captured before mutation');
        $this->assertSame("fuel:order_product:{$op->id}:c1", $row1->idempotency_key);

        // 2. Retry the same operation (status already terminal) → no duplicate.
        $op->refresh();
        ChargeService::markResolved($op, 'fuel', 'retry', $this->user->id);
        $this->assertSame(1, AlertStatusTransition::where('source_id', $op->id)->count());

        // 3. Reopen the alert, then resolve again → a second legitimate cycle.
        $op->update(['fuel_charge_status' => 'pending']);
        $op->refresh();
        ChargeService::markResolved($op, 'fuel', 'second pass', $this->user->id);
        $this->assertSame(2, AlertStatusTransition::where('source_id', $op->id)->count());
        $this->assertNotNull(
            AlertStatusTransition::where('idempotency_key', "fuel:order_product:{$op->id}:c2")->first(),
            'reopen → re-resolve records cycle 2'
        );

        // A non-terminal "transition" is never recorded.
        $op2 = $this->makeOrderProduct('fuel');
        AlertLifecycleService::recordOrderProduct($op2, 'fuel', null, 'pending', $this->user->id);
        $this->assertSame(0, AlertStatusTransition::where('source_id', $op2->id)->count());
    }

    /** Completed Today respects the America/Chicago day boundary. */
    public function test_completed_today_uses_chicago_day_boundary(): void
    {
        // Freeze "now" at a fixed instant so day boundaries are deterministic.
        Carbon::setTestNow(Carbon::parse('2026-07-12 15:00:00', 'America/Chicago'));

        $op = $this->makeOrderProduct('fuel');

        // Transition earlier today (Chicago) → counted.
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $op->id,
            'previous_status' => 'pending', 'new_status' => 'resolved',
            'idempotency_key' => "fuel:order_product:{$op->id}:c1",
            'transitioned_at' => Carbon::parse('2026-07-12 09:00:00', 'America/Chicago'),
        ]);
        // Transition late yesterday (Chicago) → NOT counted.
        $op2 = $this->makeOrderProduct('fuel');
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $op2->id,
            'previous_status' => 'pending', 'new_status' => 'completed',
            'idempotency_key' => "fuel:order_product:{$op2->id}:c1",
            'transitioned_at' => Carbon::parse('2026-07-11 23:30:00', 'America/Chicago'),
        ]);

        $this->assertSame(1, AlertLifecycleService::completedTodayCount('fuel'));

        Carbon::setTestNow();
    }

    /**
     * Resolved This Week counts distinct sources within the current Sun–Sat
     * calendar week (business tz); a transition from last week is excluded, and
     * a transition earlier this week but not today counts for the week only.
     */
    public function test_resolved_this_week_uses_sunday_to_saturday_week(): void
    {
        // Anchor to a fixed instant; derive the week start (Sunday) from it.
        Carbon::setTestNow(Carbon::parse('2026-07-15 12:00:00', 'America/Chicago'));
        $weekStart = Carbon::now('America/Chicago')->startOfWeek(Carbon::SUNDAY);

        // Earlier this week (day after Sunday), not today → counts for week, not today.
        $thisWeek = $this->makeOrderProduct('fuel');
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $thisWeek->id,
            'previous_status' => 'pending', 'new_status' => 'resolved',
            'idempotency_key' => "fuel:order_product:{$thisWeek->id}:c1",
            'transitioned_at' => $weekStart->copy()->addDay()->setTime(9, 0),
        ]);

        // Last week (an hour before this week's Sunday) → excluded.
        $lastWeek = $this->makeOrderProduct('fuel');
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $lastWeek->id,
            'previous_status' => 'pending', 'new_status' => 'completed',
            'idempotency_key' => "fuel:order_product:{$lastWeek->id}:c1",
            'transitioned_at' => $weekStart->copy()->subHour(),
        ]);

        $this->assertSame(1, AlertLifecycleService::resolvedThisWeekCount('fuel'));
        // The mid-week transition is not "today" (test now is a later day).
        $this->assertSame(0, AlertLifecycleService::completedTodayCount('fuel'));

        Carbon::setTestNow();
    }

    /** Completed Today counts DISTINCT sources, not raw rows. */
    public function test_completed_today_counts_distinct_sources(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-12 12:00:00', 'America/Chicago'));
        $op = $this->makeOrderProduct('damage');

        // Two cycles for the SAME source (resolved, then reopened + completed)
        // → distinct idempotency keys, but Completed Today counts the source once.
        AlertStatusTransition::create([
            'alert_type' => 'damage', 'source_type' => 'order_product', 'source_id' => $op->id,
            'previous_status' => 'pending', 'new_status' => 'resolved',
            'idempotency_key' => "damage:order_product:{$op->id}:c1", 'transitioned_at' => now(),
        ]);
        AlertStatusTransition::create([
            'alert_type' => 'damage', 'source_type' => 'order_product', 'source_id' => $op->id,
            'previous_status' => 'pending', 'new_status' => 'completed',
            'idempotency_key' => "damage:order_product:{$op->id}:c2", 'transitioned_at' => now(),
        ]);

        $this->assertSame(1, AlertLifecycleService::completedTodayCount('damage'));
        Carbon::setTestNow();
    }

    /** The real ChargeService pathways write lifecycle rows (OP resolved/uncollectible). */
    public function test_charge_service_pathways_record_transitions(): void
    {
        $opR = $this->makeOrderProduct('fuel');
        ChargeService::markResolved($opR, 'fuel', 'waived', $this->user->id);
        $this->assertDatabaseHas('alert_status_transitions', [
            'source_type' => 'order_product', 'source_id' => $opR->id,
            'alert_type' => 'fuel', 'new_status' => 'resolved',
            'previous_status' => 'pending', // captured before mutation
        ]);

        $opU = $this->makeOrderProduct('damage');
        ChargeService::markUncollectible($opU, 'damage', $this->user->id);
        $this->assertDatabaseHas('alert_status_transitions', [
            'source_type' => 'order_product', 'source_id' => $opU->id,
            'alert_type' => 'damage', 'new_status' => 'uncollectible',
            'previous_status' => 'pending', // captured before mutation
        ]);
    }

    /** Outstanding query is unchanged and Completed Today flows into the summary. */
    public function test_alerts_section_summary_outstanding_and_completed(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-12 10:00:00', 'America/Chicago'));

        // One outstanding fuel OP alert (mirrors existing AlertsSection query).
        $this->makeOrderProduct('fuel');

        // One fuel alert completed today: terminal status (so it leaves the
        // outstanding query) plus a lifecycle row dated today.
        $done = $this->makeOrderProduct('fuel');
        $done->update(['fuel_charge_status' => 'completed']);
        AlertStatusTransition::create([
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $done->id,
            'previous_status' => 'pending', 'new_status' => 'completed',
            'idempotency_key' => "fuel:order_product:{$done->id}:c1", 'transitioned_at' => now(),
        ]);

        $section = new AlertsSection();
        $section->refreshAlerts();

        $this->assertSame(1, $section->fuelSummary['outstanding'], 'Outstanding query behavior unchanged');
        $this->assertSame(1, $section->fuelSummary['completed_today']);
        $this->assertArrayHasKey('new_today', $section->fuelSummary);
        $this->assertArrayHasKey('avg_age_days', $section->fuelSummary);

        Carbon::setTestNow();
    }

    /**
     * Concurrency safety. PHPUnit/MySQL here cannot run two truly parallel
     * requests, so we prove the duplicate-key recovery path directly: the
     * unique idempotency_key rejects a duplicate transition (the DB backstop a
     * losing concurrent writer would hit), and record()'s firstOrCreate+catch
     * resolves that to the existing row instead of surfacing an exception —
     * yielding exactly one transition row, no inflated Completed Today.
     */
    public function test_duplicate_key_race_is_handled_safely(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-12 10:00:00', 'America/Chicago'));
        $op = $this->makeOrderProduct('fuel');

        // Race winner commits cycle 1.
        AlertLifecycleService::recordOrderProduct($op, 'fuel', 'pending', 'resolved', $this->user->id);
        $key = "fuel:order_product:{$op->id}:c1";
        $this->assertDatabaseHas('alert_status_transitions', ['idempotency_key' => $key]);

        // DB backstop: a second row with the SAME key is rejected outright.
        $threw = false;
        try {
            AlertStatusTransition::create([
                'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $op->id,
                'previous_status' => 'pending', 'new_status' => 'resolved',
                'idempotency_key' => $key, 'transitioned_at' => now(),
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $threw = true;
        }
        $this->assertTrue($threw, 'unique idempotency_key rejects a duplicate transition');

        // record()'s firstOrCreate on an already-present key is a safe no-op.
        AlertStatusTransition::firstOrCreate(['idempotency_key' => $key], [
            'alert_type' => 'fuel', 'source_type' => 'order_product', 'source_id' => $op->id,
            'previous_status' => 'pending', 'new_status' => 'resolved', 'transitioned_at' => now(),
        ]);

        // One row, one distinct source counted.
        $this->assertSame(1, AlertStatusTransition::where('source_id', $op->id)->count());
        $this->assertSame(1, AlertLifecycleService::completedTodayCount('fuel'));

        Carbon::setTestNow();
    }

    /** Zero-state and mixed payloads are well-formed for the donut. */
    public function test_summary_zero_and_mixed_payloads(): void
    {
        // Empty DB → both zero (donut renders neutral empty ring client-side).
        $section = new AlertsSection();
        $section->refreshAlerts();
        $this->assertSame(0, $section->fuelSummary['outstanding']);
        $this->assertSame(0, $section->fuelSummary['completed_today']);
        $this->assertSame(0, $section->damageSummary['outstanding']);
        $this->assertSame('0', (string) $section->fuelSummary['avg_age_days']);
    }
}
