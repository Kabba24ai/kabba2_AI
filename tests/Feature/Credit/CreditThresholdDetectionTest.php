<?php

namespace Tests\Feature\Credit;

use App\Enums\Credit\CreditReviewTaskOutcome;
use App\Helpers\CustomHelper;
use App\Models\Credit\CreditThresholdEvent;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Tasks\Task;
use App\Services\Billing\PrimaryBillingAdminResolver;
use App\Services\LedgerBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Credit Threshold Exception — detection semantics. Proves the monitor fires
 * exactly on genuine new exposure that crosses/extends beyond an APPROVED
 * limit, defers past commit (rollback → no event), collapses a multi-product
 * order to one event, and never fires for non-credit customers, decreases,
 * balance-neutral edits, or repair re-writes.
 */
class CreditThresholdDetectionTest extends TestCase
{
    use RefreshDatabase;

    private function approvedCreditCustomer(float $limit = 1000, float $balance = 0): Customer
    {
        return Customer::create([
            'first_name' => 'Credit', 'last_name' => 'Account',
            'email' => 'credit-' . uniqid() . '@test.local', 'status' => 'Active',
            'is_credit_account' => 1, 'credit_limit' => $limit,
            'available_credit_balance' => $balance,
        ]);
    }

    private function designatePrimaryAdmin(): User
    {
        $admin = User::create([
            'first_name' => 'Billing', 'last_name' => 'Admin',
            'email' => 'billing-admin-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);

        \App\Models\Configurations\Setting::updateOrCreate(
            ['setting_name' => PrimaryBillingAdminResolver::SETTING_NAME],
            [
                'setting_type'  => PrimaryBillingAdminResolver::SETTING_TYPE,
                'setting_value' => $admin->id,
            ],
        );

        return $admin;
    }

    /** Post a fresh, tax-free A/R charge and run it through the production writer. */
    private function postCharge(Customer $customer, float $amount, ?int $orderId = null, string $reason = 'Test Charge'): CustomerAccount
    {
        $account = new CustomerAccount();
        $account->customer_id = $customer->id;
        $account->amount = $amount;
        $account->reason = $reason;
        $account->type = 'charge';
        $account->date = now();
        $account->sales_tax = 0;
        $account->sales_tax_type = 'free';
        $account->order_id = $orderId;
        $account->save();

        CustomHelper::updateCreditBalance($account);

        return $account;
    }

    public function test_crossing_the_limit_records_one_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->designatePrimaryAdmin();

        $this->postCharge($customer, 1200);

        $events = CreditThresholdEvent::all();
        $this->assertCount(1, $events, 'a single crossing must record exactly one event');

        $event = $events->first();
        $this->assertEquals($customer->id, $event->customer_id);
        $this->assertEquals(1000, (float) $event->credit_limit_at_time);
        $this->assertEquals(0, (float) $event->balance_before);
        $this->assertEquals(1200, (float) $event->balance_after);
        $this->assertEquals(1200, (float) $event->exposure_added);
        $this->assertEquals(200, (float) $event->amount_over_limit);
    }

    public function test_additional_exposure_while_already_over_records_a_second_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->designatePrimaryAdmin();

        $this->postCharge($customer, 1200); // crossing
        $this->postCharge($customer, 300);  // more exposure while over

        $this->assertEquals(2, CreditThresholdEvent::count(), 'each qualifying posting logs its own event');
    }

    public function test_balance_neutral_edit_while_over_does_not_fire(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $charge = $this->postCharge($customer, 1200); // 1 event
        $this->assertEquals(1, CreditThresholdEvent::count());

        // Re-fetch → wasRecentlyCreated is false → an edit re-write, not new exposure.
        $existing = CustomerAccount::find($charge->id);
        CustomHelper::updateCreditBalance($existing);

        $this->assertEquals(1, CreditThresholdEvent::count(), 'an edit re-write must not fire a new event');
    }

    public function test_payment_decrease_does_not_fire(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->postCharge($customer, 1200); // over, 1 event
        $this->assertEquals(1, CreditThresholdEvent::count());

        $payment = new CustomerAccount();
        $payment->customer_id = $customer->id;
        $payment->amount = 500;
        $payment->reason = 'Payment';
        $payment->type = 'payment';
        $payment->date = now();
        $payment->sales_tax = 0;
        $payment->sales_tax_type = 'free';
        $payment->save();
        CustomHelper::updateCreditBalance($payment);

        $this->assertEquals(1, CreditThresholdEvent::count(), 'a decreasing posting must not fire');
    }

    public function test_non_credit_customer_never_fires(): void
    {
        $customer = Customer::create([
            'first_name' => 'Cash', 'last_name' => 'Only',
            'email' => 'cash-' . uniqid() . '@test.local', 'status' => 'Active',
            'is_credit_account' => 0, 'credit_limit' => null,
            'available_credit_balance' => 0,
        ]);

        $this->postCharge($customer, 5000);

        $this->assertEquals(0, CreditThresholdEvent::count(), 'non-credit customers are excluded');
    }

    public function test_cr1_defect_row_limit_without_approval_does_not_fire(): void
    {
        // CR-1 defect shape: a limit set but not approved. Must NOT fire.
        $customer = Customer::create([
            'first_name' => 'Defect', 'last_name' => 'Row',
            'email' => 'defect-' . uniqid() . '@test.local', 'status' => 'Active',
            'is_credit_account' => 0, 'credit_limit' => 1000,
            'available_credit_balance' => 0,
        ]);

        $this->postCharge($customer, 1200);

        $this->assertEquals(0, CreditThresholdEvent::count(), 'approved-accounts-only gate excludes CR-1 rows');
    }

    public function test_charge_under_limit_does_not_fire(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->postCharge($customer, 400);

        $this->assertEquals(0, CreditThresholdEvent::count());
    }

    public function test_rolled_back_posting_produces_no_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);

        try {
            DB::transaction(function () use ($customer) {
                $this->postCharge($customer, 1200);
                throw new \RuntimeException('force rollback');
            });
        } catch (\RuntimeException $e) {
            // expected
        }

        $this->assertEquals(0, CreditThresholdEvent::count(), 'afterCommit must discard on rollback');
    }

    public function test_multi_product_order_collapses_to_one_event(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'customer_name' => $customer->full_name,
            'subtotal' => 1500, 'tax_amount' => 0, 'grand_total' => 1500,
        ]);

        // Two order-type postings for the SAME order — first crosses, second adds.
        foreach ([700, 800] as $amount) {
            $row = new CustomerAccount();
            $row->customer_id = $customer->id;
            $row->amount = $amount;
            $row->reason = 'On-Account Order';
            $row->type = 'order';
            $row->date = now();
            $row->sales_tax = 0;
            $row->sales_tax_type = 'free';
            $row->order_id = $order->id;
            $row->save();
            LedgerBalanceService::applyTransaction($row);
        }

        $this->assertEquals(1, CreditThresholdEvent::count(), 'one order = one event even across product lines');
        $event = CreditThresholdEvent::first();
        $this->assertEquals($order->id, $event->triggering_order_id);
    }

    public function test_repeat_crossings_maintain_one_open_task_and_append(): void
    {
        $customer = $this->approvedCreditCustomer(1000);
        $this->designatePrimaryAdmin();

        $this->postCharge($customer, 1200); // creates task
        $this->postCharge($customer, 300);  // should append, not create a 2nd task

        $this->assertEquals(2, CreditThresholdEvent::count());
        $this->assertEquals(1, Task::where('related_customer_id', $customer->id)->count(), 'one open task per customer');

        $outcomes = CreditThresholdEvent::orderBy('id')->pluck('task_outcome')->all();
        $this->assertEquals(CreditReviewTaskOutcome::Created, $outcomes[0]);
        $this->assertEquals(CreditReviewTaskOutcome::Appended, $outcomes[1]);
    }
}
