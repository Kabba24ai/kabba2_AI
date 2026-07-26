<?php

namespace Tests\Feature\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Enums\Credit\CreditThresholdSourceType;
use App\Events\Credit\CreditThresholdExceededEvent;
use App\Helpers\CustomHelper;
use App\Models\Configurations\Setting;
use App\Models\Credit\CreditThresholdEvent;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Tasks\Task;
use App\Services\Billing\PrimaryBillingAdminResolver;
use App\Services\Credit\CreditThresholdSnapshot;
use App\Services\LedgerBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Credit Threshold Exception — idempotency identifies the unique POSTING
 * EPISODE. A multi-line order booking collapses to one event; a later,
 * independent posting on the SAME order creates another; a retry of the same
 * posting does not; two distinct postings of the same amount both count.
 */
class CreditThresholdIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private function approvedCreditCustomer(float $limit = 1000): Customer
    {
        return Customer::create([
            'first_name' => 'Credit', 'last_name' => 'Account',
            'email' => 'credit-' . uniqid() . '@test.local', 'status' => 'Active',
            'is_credit_account' => 1, 'credit_limit' => $limit, 'available_credit_balance' => 0,
        ]);
    }

    private function designatePrimaryAdmin(): User
    {
        $admin = User::create([
            'first_name' => 'Billing', 'last_name' => 'Admin',
            'email' => 'admin-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);
        Setting::updateOrCreate(
            ['setting_name' => PrimaryBillingAdminResolver::SETTING_NAME],
            ['setting_type' => PrimaryBillingAdminResolver::SETTING_TYPE, 'setting_value' => $admin->id],
        );

        return $admin;
    }

    private function makeOrder(Customer $customer): int
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100,
        ])->id;
    }

    /** One order-booking ledger line (type='order') through the production writer. */
    private function postOrderLine(Customer $customer, float $amount, int $orderId): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $customer->id;
        $row->amount = $amount;
        $row->reason = 'On-Account Order';
        $row->type = 'order';
        $row->date = now();
        $row->sales_tax = 0;
        $row->sales_tax_type = 'free';
        $row->order_id = $orderId;
        $row->save();
        LedgerBalanceService::applyTransaction($row);
    }

    /** One A/R charge (type='charge'), optionally linked to an order. */
    private function postCharge(Customer $customer, float $amount, ?int $orderId = null, string $reason = 'Fuel Charge'): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $customer->id;
        $row->amount = $amount;
        $row->reason = $reason;
        $row->type = 'charge';
        $row->date = now();
        $row->sales_tax = 0;
        $row->sales_tax_type = 'free';
        $row->order_id = $orderId;
        $row->save();
        CustomHelper::updateCreditBalance($row);
    }

    // ── Scenario 1: same posting, same order → 1 event ──────────────────

    public function test_multi_line_order_posting_records_one_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $orderId = $this->makeOrder($customer);

        // Three lines of one order booking; first crosses, rest add while over.
        $this->postOrderLine($customer, 700, $orderId); // 700
        $this->postOrderLine($customer, 500, $orderId); // 1200 (crosses)
        $this->postOrderLine($customer, 300, $orderId); // 1500

        $this->assertEquals(1, CreditThresholdEvent::count());
        $this->assertEquals("order:{$orderId}", CreditThresholdEvent::first()->idempotency_key);
    }

    // ── Scenario 2: later posting, same order → 2 events, 1 task ─────────

    public function test_later_charge_on_same_order_creates_a_distinct_event(): void
    {
        $this->designatePrimaryAdmin();
        $customer = $this->approvedCreditCustomer(1000);
        $orderId = $this->makeOrder($customer);

        // Initial on-account order crosses the limit.
        $this->postOrderLine($customer, 1200, $orderId);
        // A separate, later exposure (e.g. a fuel charge) on the SAME order.
        $this->postCharge($customer, 300, $orderId, 'Fuel Charge');

        $events = CreditThresholdEvent::orderBy('id')->get();
        $this->assertCount(2, $events, 'a later independent posting on the same order is its own event');
        $this->assertEquals("order:{$orderId}", $events[0]->idempotency_key);
        $this->assertStringStartsWith('acct:', $events[1]->idempotency_key);

        // One open review task; the second event appended to it.
        $this->assertEquals(1, Task::where('related_customer_id', $customer->id)->count());
        $this->assertEquals(CreditReviewTaskOutcome::Created, $events[0]->task_outcome);
        $this->assertEquals(CreditReviewTaskOutcome::Appended, $events[1]->task_outcome);
        $this->assertEquals($events[0]->task_id, $events[1]->task_id);
    }

    // ── Scenario 3: retry of the same posting → 1 event ─────────────────

    public function test_retry_of_the_same_posting_records_one_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $snapshot = $this->snapshotFor('acct:9999', $customer);

        // Listener invoked twice for the identical posting (deadlock retry /
        // double dispatch).
        event(new CreditThresholdExceededEvent($snapshot));
        event(new CreditThresholdExceededEvent($snapshot));

        $this->assertEquals(1, CreditThresholdEvent::count());
    }

    // ── Scenario 4: different postings, identical amounts → 2 events ─────

    public function test_two_distinct_charges_of_the_same_amount_record_two_events(): void
    {
        $customer = $this->approvedCreditCustomer(1000);

        $this->postCharge($customer, 1200, null, 'Damage Charge'); // crosses
        $this->postCharge($customer, 1200, null, 'Damage Charge'); // distinct posting, more exposure

        $this->assertEquals(2, CreditThresholdEvent::count());
        $keys = CreditThresholdEvent::pluck('idempotency_key')->all();
        $this->assertCount(2, array_unique($keys), 'distinct postings get distinct keys');
    }

    // ── Source identity + no fabricated order id ────────────────────────

    public function test_non_order_charge_records_source_identity_without_an_order_id(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->postCharge($customer, 1200, null, 'Manual Account Charge');

        $event = CreditThresholdEvent::firstOrFail();
        $this->assertNull($event->triggering_order_id, 'no fabricated order id for a non-order posting');
        $this->assertNotNull($event->triggering_account_row_id, 'the canonical A/R posting id is recorded');
        $this->assertEquals(CreditThresholdSourceType::AccountCharge, $event->source_type);
        $this->assertEquals('Manual Account Charge', $event->source_detail);
        $this->assertStringStartsWith('acct:', $event->idempotency_key);
    }

    // ── DB-level uniqueness + no duplicate task comment ─────────────────

    public function test_database_enforces_idempotency_key_uniqueness(): void
    {
        CreditThresholdEvent::create($this->eventRow('acct:dup'));

        $this->expectException(\Illuminate\Database\QueryException::class);
        // Bypass the model firstOrCreate guard to prove the DB constraint itself.
        DB::table('credit_threshold_events')->insert($this->eventRow('acct:dup') + [
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_retry_does_not_append_a_duplicate_task_comment(): void
    {
        $this->designatePrimaryAdmin();
        $customer = $this->approvedCreditCustomer(1000);
        $orderId = $this->makeOrder($customer);

        $this->postOrderLine($customer, 1200, $orderId);   // event 1 → task (no comment)
        $this->postCharge($customer, 300, null, 'Fuel');   // event 2 → appended (1 comment)

        $task = Task::where('related_customer_id', $customer->id)->firstOrFail();
        $this->assertEquals(1, $task->comments()->count());

        // Retry the second posting's event — must not append again.
        $second = CreditThresholdEvent::orderByDesc('id')->first();
        event(new CreditThresholdExceededEvent($this->snapshotFor($second->idempotency_key, $customer)));

        $this->assertEquals(1, $task->fresh()->comments()->count(), 'a duplicate posting adds no comment');
        $this->assertEquals(2, CreditThresholdEvent::count());
    }

    // ── helpers ─────────────────────────────────────────────────────────

    private function snapshotFor(string $key, Customer $customer): CreditThresholdSnapshot
    {
        return new CreditThresholdSnapshot(
            idempotencyKey: $key,
            customerId: (int) $customer->id,
            customerName: $customer->full_name,
            orderId: null,
            accountRowId: null, // synthetic snapshot — avoid the customer_accounts FK
            sourceType: CreditThresholdSourceType::AccountCharge,
            sourceDetail: 'Retry Test',
            creditLimit: 1000, balanceBefore: 900, exposureAdded: 300,
            balanceAfter: 1200, amountOverLimit: 200,
            responsibleUserId: null, responsibleContext: 'test', occurredAt: now(),
        );
    }

    private function eventRow(string $key): array
    {
        return [
            'idempotency_key' => $key,
            'source_type' => 'account_charge',
            'credit_limit_at_time' => 1000, 'balance_before' => 900, 'exposure_added' => 300,
            'balance_after' => 1200, 'amount_over_limit' => 200, 'occurred_at' => now(),
            'review_status' => 'open',
        ];
    }
}
