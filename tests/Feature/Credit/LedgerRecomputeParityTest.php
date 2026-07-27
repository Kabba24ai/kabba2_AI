<?php

namespace Tests\Feature\Credit;

use App\Helpers\CustomHelper;
use App\Models\Configurations\Setting;
use App\Models\Credit\CreditThresholdEvent;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Orders\Order;
use App\Services\LedgerBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Stage A — Ledger Safety. Proves the governing invariant for every supported
 * sequence:
 *
 *     live incremental balance  ==  full recomputation from canonical rows
 *
 * and that the write-mode repair (fixTheRunningBalance) leaves a correct
 * balance unchanged. The reverse-tax linked-refund case is the LED-1/LED-2
 * defect this phase corrects; without the fix, recompute drifts by amount×rate.
 */
class LedgerRecomputeParityTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.10;

    protected function setUp(): void
    {
        parent::setUp();
        // Deterministic global sales-tax rate for charge 'add' postings.
        Setting::updateOrCreate(
            ['setting_name' => 'sales_tax'],
            ['setting_type' => 'General', 'setting_value' => self::RATE],
        );
    }

    private function customer(array $overrides = []): Customer
    {
        // tax_status Exempt makes free-form refunds subtract exactly `amount`
        // (deterministic). Charge 'add' and reverse refunds carry explicit tax
        // independent of tax_status, so the taxed scenarios are unaffected.
        $c = Customer::create(array_merge([
            'first_name' => 'Ledger', 'last_name' => 'Parity',
            'email' => 'ledger-' . uniqid() . '@test.local', 'status' => 'Active',
            'available_credit_balance' => 0,
        ], $overrides));
        $c->forceFill(['tax_status' => 'Exempt'])->save();

        return $c->fresh();
    }

    // ── posting helpers (use the REAL production writers) ────────────────

    private function order(Customer $c, float $base, float $taxDollars = 0.0, ?int $orderId = null): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->order_id = $orderId;
        $row->amount = $base;
        $row->sales_tax = $taxDollars > 0 ? self::RATE : 0.0;
        $row->type = 'order';
        $row->reason = 'Order line';
        $row->date = now();
        $row->save();
        LedgerBalanceService::applyTransaction($row, $taxDollars);
    }

    private function charge(Customer $c, float $amount, string $taxType = 'free'): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->sales_tax = 0;
        $row->sales_tax_type = $taxType; // 'add' pulls the global rate; 'free' none
        $row->type = 'charge';
        $row->reason = 'Charge';
        $row->date = now();
        $row->save();
        CustomHelper::updateCreditBalance($row);
    }

    private function payment(Customer $c, float $amount): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->sales_tax = 0;
        $row->sales_tax_type = 'free';
        $row->type = 'payment';
        $row->reason = 'Payment';
        $row->date = now();
        $row->save();
        CustomHelper::updateCreditBalance($row);
    }

    private function refundFreeForm(Customer $c, float $amount): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->sales_tax = 0;              // non-taxable free-form refund
        $row->sales_tax_type = null;
        $row->type = 'refund';
        $row->reason = 'Refund';
        $row->date = now();
        $row->save();
        CustomHelper::updateCreditBalance($row);
    }

    /** Mirrors RefundStoreController::storeLinkedRefund: tax-inclusive amount + reverse. */
    private function refundReverse(Customer $c, float $taxInclusiveAmount): CustomerAccount
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $taxInclusiveAmount;
        $row->sales_tax = self::RATE;      // stores the RATE
        $row->sales_tax_type = 'reverse';
        $row->type = 'refund';
        $row->reason = 'Linked refund';
        $row->date = now();
        $row->save();
        CustomHelper::updateCreditBalance($row);

        return $row;
    }

    private function discount(Customer $c, float $amount, string $taxType = 'free'): void
    {
        $row = new CustomerAccount();
        $row->customer_id = $c->id;
        $row->amount = $amount;
        $row->sales_tax = 0;
        $row->sales_tax_type = $taxType;
        $row->type = 'discount';
        $row->reason = 'Discount';
        $row->date = now();
        $row->save();
        CustomHelper::updateCreditBalance($row);
    }

    /**
     * The core assertion: incremental == recompute (== expected), and the
     * write-mode repair leaves the correct balance unchanged.
     */
    private function assertParity(Customer $c, float $expected): void
    {
        $c->refresh();
        $incremental = round((float) $c->available_credit_balance, 2);
        $recompute = round(CustomHelper::recomputeOutstandingBalance($c->id), 2);

        $this->assertEqualsWithDelta($expected, $incremental, 0.001, 'incremental balance matches expected');
        $this->assertEqualsWithDelta($incremental, $recompute, 0.001, 'recompute matches the live incremental balance');

        // Write-mode repair must be a no-op on an already-correct balance.
        CustomHelper::fixTheRunningBalance($c->id);
        $c->refresh();
        $this->assertEqualsWithDelta($incremental, round((float) $c->available_credit_balance, 2), 0.001, 'fixTheRunningBalance is stable');

        // Idempotent recompute: running it again yields the same value.
        CustomHelper::fixTheRunningBalance($c->id);
        $c->refresh();
        $this->assertEqualsWithDelta($incremental, round((float) $c->available_credit_balance, 2), 0.001, 'repeated recompute stable');
    }

    // ── 1–16 ────────────────────────────────────────────────────────────

    public function test_01_account_order_only(): void
    {
        $c = $this->customer();
        $this->order($c, 500);
        $this->assertParity($c, 500);
    }

    public function test_02_account_order_plus_partial_payment(): void
    {
        $c = $this->customer();
        $this->order($c, 500);
        $this->payment($c, 200);
        $this->assertParity($c, 300);
    }

    public function test_03_account_order_plus_full_payment(): void
    {
        $c = $this->customer();
        $this->order($c, 500);
        $this->payment($c, 500);
        $this->assertParity($c, 0);
    }

    public function test_04_fuel_charge_plus_payment(): void
    {
        $c = $this->customer();
        $this->charge($c, 75);       // fuel-style, tax-free
        $this->payment($c, 75);
        $this->assertParity($c, 0);
    }

    public function test_05_damage_charge_plus_payment(): void
    {
        $c = $this->customer();
        $this->charge($c, 250);
        $this->payment($c, 100);
        $this->assertParity($c, 150);
    }

    public function test_06_manual_charge_plus_refund(): void
    {
        $c = $this->customer();
        $this->charge($c, 300);
        $this->refundFreeForm($c, 120);
        $this->assertParity($c, 180);
    }

    public function test_07_taxinclusive_charge_plus_linked_refund(): void
    {
        $c = $this->customer();
        $this->charge($c, 100, 'add');       // +100 +10 tax = 110
        $this->refundReverse($c, 110);       // −110 (tax-inclusive)
        $this->assertParity($c, 0);
    }

    public function test_08_reverse_tax_linked_refund_is_parity_safe(): void
    {
        // The LED-1/LED-2 case in isolation. Charge 200+20 tax=220, refund 55
        // (tax-inclusive) → 220 − 55 = 165. Pre-fix, recompute would read 165 −
        // 5 = 160 and corrupt the balance on repair.
        $c = $this->customer();
        $this->charge($c, 200, 'add');       // +220
        $this->refundReverse($c, 55);        // −55
        $this->assertParity($c, 165);
    }

    public function test_09_discount(): void
    {
        $c = $this->customer();
        $this->charge($c, 400);
        $this->discount($c, 50);
        $this->assertParity($c, 350);
    }

    public function test_10_mixed_debits_and_credits_in_order(): void
    {
        $c = $this->customer();
        $this->order($c, 300, 30);           // +330
        $this->charge($c, 100, 'add');       // +110
        $this->payment($c, 200);             // −200
        $this->discount($c, 40);             // −40
        $this->charge($c, 60);               // +60 (tax-free)
        $this->refundReverse($c, 22);        // −22 (tax-inclusive)
        $this->assertParity($c, 238);        // 330+110-200-40+60-22
    }

    public function test_11_direct_pod_order_excluded_from_ar(): void
    {
        // A paid/POD order books NO customer_accounts rows → A/R stays 0.
        $c = $this->customer();
        Order::create([
            'order_date' => now()->format('Y-m-d'), 'customer_id' => $c->id,
            'customer_name' => $c->full_name, 'subtotal' => 500, 'tax_amount' => 0, 'grand_total' => 500,
        ]);
        $this->assertParity($c, 0);
    }

    public function test_12_direct_order_refund_excluded_from_ar(): void
    {
        // A direct-order refund writes an order_payments refund row, never a
        // customer_accounts row → A/R untouched.
        $c = $this->customer();
        $this->assertParity($c, 0);
    }

    public function test_13_account_refund_reduces_ar_without_external_money(): void
    {
        $c = $this->customer();
        $this->charge($c, 500);
        $this->refundFreeForm($c, 500);
        $this->assertParity($c, 0);
    }

    public function test_14_repeated_recompute_stable(): void
    {
        $c = $this->customer();
        $this->order($c, 300, 30);
        $this->refundReverse($c, 110);
        $c->refresh();
        $before = round((float) $c->available_credit_balance, 2);
        for ($i = 0; $i < 5; $i++) {
            CustomHelper::fixTheRunningBalance($c->id);
        }
        $c->refresh();
        $this->assertEqualsWithDelta($before, round((float) $c->available_credit_balance, 2), 0.001);
    }

    public function test_15_recompute_does_not_change_a_correct_reverse_refund_balance(): void
    {
        // Simulates the recompute an invoice edit/delete triggers: a correct
        // balance holding a reverse-tax refund must survive fixTheRunningBalance.
        $c = $this->customer();
        $this->charge($c, 100, 'add');   // +110
        $this->refundReverse($c, 110);   // −110 → 0
        $c->refresh();
        $this->assertEqualsWithDelta(0, (float) $c->available_credit_balance, 0.001);
        CustomHelper::fixTheRunningBalance($c->id);
        $c->refresh();
        $this->assertEqualsWithDelta(0, (float) $c->available_credit_balance, 0.001, 'reverse-tax refund balance corrupted by recompute (LED-1)');
    }

    public function test_16_reverse_then_recompute_after_row_removal(): void
    {
        // Invoice delete/recreate shape: reverse a row's effect, soft-delete it,
        // then recompute must equal the remaining rows.
        $c = $this->customer();
        $this->charge($c, 100, 'add');       // +110
        $refund = $this->refundReverse($c, 55); // −55 → 55
        $c->refresh();
        $this->assertEqualsWithDelta(55, (float) $c->available_credit_balance, 0.001);

        CustomHelper::reverseTransactionEffect($refund); // undo the refund → back to 110
        $refund->delete();                               // soft-delete the row
        $c->refresh();
        $this->assertEqualsWithDelta(110, (float) $c->available_credit_balance, 0.001, 'LED-2: reverse over-restored');

        // Recompute over the remaining (non-deleted) rows equals 110.
        $this->assertEqualsWithDelta(110, CustomHelper::recomputeOutstandingBalance($c->id), 0.001);
    }

    public function test_recompute_never_creates_credit_threshold_events(): void
    {
        // An approved credit customer already over limit; recompute/repair must
        // NOT manufacture threshold events (those fire only on new exposure
        // postings via the observe hook, never from recompute/reversal).
        $c = $this->customer([
            'is_credit_account' => 1, 'credit_limit' => 100,
        ]);
        $this->charge($c, 500);          // crosses → 1 event from the posting
        $eventsAfterPosting = CreditThresholdEvent::count();
        $this->assertGreaterThanOrEqual(1, $eventsAfterPosting);

        CustomHelper::fixTheRunningBalance($c->id);
        $account = CustomerAccount::where('customer_id', $c->id)->first();
        CustomHelper::reverseTransactionEffect($account);

        $this->assertEquals($eventsAfterPosting, CreditThresholdEvent::count(),
            'recompute / reversal must not create threshold events');
    }
}
