<?php

namespace Tests\Feature\Discounts;

use App\Enums\Discounts\DiscountTargetType;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\CustomerCredit;
use App\Models\Orders\Order;
use App\Services\CustomerCreditService;
use App\Services\Discounts\DiscountApplicationService;
use App\Services\Discounts\DiscountCalculator;
use App\Services\Discounts\DiscountException;
use App\Services\Discounts\DiscountTargetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Increment 2 — POD / rental Order surface. Store Credit is a PRE-TAX discount:
 * reduce product base → recompute tax on the discounted base → new grand_total /
 * balance_due, preserving fees and creating NO payment record.
 */
class DiscountOrderIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private const RATE = 0.0975;

    private function service(): DiscountApplicationService
    {
        return new DiscountApplicationService(new DiscountCalculator(), new DiscountTargetResolver());
    }

    private function customer(float $credit): Customer
    {
        $c = Customer::create([
            'first_name' => 'POD', 'last_name' => 'Cust',
            'email' => 'pod-' . uniqid() . '@test.local', 'status' => 'Active',
        ]);
        CustomerCredit::create(['customer_id' => $c->id, 'type' => 'grant', 'amount' => $credit, 'reason' => 'seed']);
        return $c;
    }

    private function order(Customer $c, float $subtotal, float $tax, float $grand): Order
    {
        return Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $c->id,
            'customer_name' => $c->full_name,
            'subtotal' => $subtotal, 'tax_amount' => $tax, 'grand_total' => $grand,
        ]);
    }

    private function assertNoPaymentRecords(int $orderId): void
    {
        $this->assertEquals(0, DB::table('order_payments')->where('order_id', $orderId)->count());
        $this->assertEquals(0, DB::table('customer_accounts')->where('type', 'payment')->count());
    }

    public function test_partial_store_credit_reprices_order(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1097.50);

        $this->service()->applyStoreCredit(DiscountTargetType::Order, $o->id, 500.00, 'ord-partial', null);

        $o->refresh();
        $this->assertEquals(500.00, (float) $o->pretax_discount_total);
        $this->assertEquals(48.75, (float) $o->tax_amount, 'tax recomputed on discounted $500');
        $this->assertEquals(548.75, (float) $o->grand_total);
        $this->assertEquals(548.75, (float) $o->balance_due);
        $this->assertEquals(1000.00, (float) $o->subtotal, 'subtotal (gross) untouched');
        $this->assertEquals(500.00, CustomerCreditService::remainingBalance($c->id));
        $this->assertNoPaymentRecords($o->id);
    }

    public function test_full_store_credit_zeroes_balance_no_payment(): void
    {
        $c = $this->customer(500);
        $o = $this->order($c, 500.00, 48.75, 548.75);

        $this->service()->applyStoreCredit(DiscountTargetType::Order, $o->id, 500.00, 'ord-full', null);

        $o->refresh();
        $this->assertEquals(0.00, (float) $o->tax_amount);
        $this->assertEquals(0.00, (float) $o->grand_total);
        $this->assertEquals(0.00, (float) $o->balance_due);
        $this->assertTrue((bool) $o->is_paid, 'fully discounted → satisfied, no invented status');
        $this->assertNoPaymentRecords($o->id);
    }

    public function test_fees_are_preserved_through_discount(): void
    {
        // subtotal 1000, tax 97.50, fees 102.50 → grand 1200.
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1200.00);

        $this->service()->applyStoreCredit(DiscountTargetType::Order, $o->id, 400.00, 'ord-fees', null);

        $o->refresh();
        // remaining base 600 → tax 58.50; fees 102.50 preserved → grand 761.00
        $this->assertEquals(58.50, (float) $o->tax_amount);
        $this->assertEquals(761.00, (float) $o->grand_total);
    }

    public function test_remaining_balance_ready_for_normal_payment(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1097.50);
        $this->service()->applyStoreCredit(DiscountTargetType::Order, $o->id, 500.00, 'ord-rem', null);
        $o->refresh();
        // The remainder is collected via the existing payment system (not our concern);
        // we only guarantee the discounted balance is correct.
        $this->assertEquals(548.75, (float) $o->balance_due);
    }

    public function test_order_posted_to_account_is_rejected(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1097.50);
        // Simulate an on-account posting (A/R ledger row) → post-posting, out of scope.
        $acc = new CustomerAccount();
        $acc->customer_id = $c->id;
        $acc->order_id = $o->id;
        $acc->amount = 1097.50;
        $acc->type = 'order';
        $acc->date = now();
        $acc->sales_tax = 0;
        $acc->sales_tax_type = 'free';
        $acc->save();

        $this->expectException(DiscountException::class);
        $this->service()->applyStoreCredit(DiscountTargetType::Order, $o->id, 100.00, 'ord-posted', null);
    }

    public function test_reversal_restores_order_pricing_and_credit(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1097.50);
        $svc = $this->service();
        $d = $svc->applyStoreCredit(DiscountTargetType::Order, $o->id, 500.00, 'ord-rev', null);
        $o->refresh();
        $this->assertEquals(548.75, (float) $o->grand_total);

        $svc->reverseStoreCredit($d);
        $o->refresh();
        $this->assertEquals(0.00, (float) $o->pretax_discount_total);
        $this->assertEquals(97.50, (float) $o->tax_amount, 'tax restored');
        $this->assertEquals(1097.50, (float) $o->grand_total, 'grand total restored');
        $this->assertEquals(1000.00, CustomerCreditService::remainingBalance($c->id), 'credit restored');
    }

    public function test_second_application_stacks_on_same_order(): void
    {
        $c = $this->customer(1000);
        $o = $this->order($c, 1000.00, 97.50, 1097.50);
        $svc = $this->service();
        $svc->applyStoreCredit(DiscountTargetType::Order, $o->id, 300.00, 's1', null);
        $svc->applyStoreCredit(DiscountTargetType::Order, $o->id, 200.00, 's2', null);
        $o->refresh();
        // 500 discounted → tax on 500 = 48.75, grand 548.75
        $this->assertEquals(500.00, (float) $o->pretax_discount_total);
        $this->assertEquals(48.75, (float) $o->tax_amount);
        $this->assertEquals(548.75, (float) $o->grand_total);
        $this->assertEquals(500.00, CustomerCreditService::remainingBalance($c->id));
    }
}
