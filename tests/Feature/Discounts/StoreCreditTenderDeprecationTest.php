<?php

namespace Tests\Feature\Discounts;

use App\Enums\Customers\PaymentMethod;
use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use App\Services\ChargeService;
use App\Services\Discounts\DiscountException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Store Credit tender DEPRECATION. Store Credit is a discount, not a tender:
 * absent from every collection dropdown/validation, rejected as a new payment,
 * no service can write a Store Credit payment — while legacy rows still render
 * and refund-to-credit remains a valid destination.
 */
class StoreCreditTenderDeprecationTest extends TestCase
{
    use RefreshDatabase;

    // ── 1: absent from every collection selection ───────────────────────

    public function test_store_credit_absent_from_payment_method_selections(): void
    {
        $this->assertNotContains(PaymentMethod::StoreCredit, PaymentMethod::canonical());
        $this->assertArrayNotHasKey('StoreCredit', PaymentMethod::options());

        $this->assertNotContains(OrderPaymentMethod::StoreCredit, OrderPaymentMethod::canonical());

        // ...but it REMAINS a FILTER option: historical orders were paid with
        // the (now-retired) Store Credit tender, and the Orders filter must
        // still isolate those legacy rows. Present in filterOptions() (a
        // read-only filter), never in canonical() (the collection dropdown).
        $this->assertContains(OrderPaymentMethod::StoreCredit, OrderPaymentMethod::filterOptions());
    }

    // ── refund destination still admits Store Credit (issuance, not tender) ──

    public function test_store_credit_is_a_valid_refund_destination(): void
    {
        $this->assertContains(PaymentMethod::StoreCredit, PaymentMethod::refundDestinations());
        // But NOT a collection method.
        $this->assertNotContains(PaymentMethod::StoreCredit, PaymentMethod::canonical());
    }

    // ── 12/13: historical rows remain readable, but not newly selectable ────

    public function test_legacy_store_credit_payment_row_still_renders(): void
    {
        // A legacy stored value must still hydrate + label without throwing.
        $this->assertEquals('Store Credit', OrderPaymentMethod::from('StoreCredit')->label());
        $this->assertEquals('Store Credit', PaymentMethod::from('StoreCredit')->label());

        $customer = Customer::create(['first_name' => 'L', 'last_name' => 'Egacy', 'email' => 'leg-' . uniqid() . '@test.local', 'status' => 'Active']);
        $order = Order::create(['order_number' => 'L' . strtoupper(uniqid()), 'order_date' => now()->toDateString(), 'customer_id' => $customer->id, 'customer_name' => 'L', 'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100]);
        $payment = $order->payments()->create(['payment_method' => 'StoreCredit', 'amount' => 100, 'status' => OrderPaymentStatus::Paid->value, 'payment_datetime' => now()]);

        $pm = $payment->fresh()->payment_method;
        $this->assertEquals('StoreCredit', $pm instanceof OrderPaymentMethod ? $pm->value : $pm, 'historical value intact');
    }

    // ── 5: no service can create a Store Credit payment row ─────────────────

    public function test_charge_service_record_payment_rejects_store_credit(): void
    {
        $customer = Customer::create(['first_name' => 'S', 'last_name' => 'Vc', 'email' => 'svc-' . uniqid() . '@test.local', 'status' => 'Active']);
        $user = User::create(['first_name' => 'S', 'last_name' => 'User', 'email' => 'svc-u-' . uniqid() . '@test.local', 'status' => 'Active']);
        $order = Order::create(['order_number' => 'S' . strtoupper(uniqid()), 'order_date' => now()->toDateString(), 'customer_id' => $customer->id, 'customer_name' => 'S', 'subtotal' => 100, 'tax_amount' => 0, 'grand_total' => 100]);
        $op = OrderProduct::create(['order_id' => $order->id, 'product_name' => 'P', 'price' => 100, 'quantity' => 1, 'sub_total' => 100, 'tax' => 0, 'total' => 100]);

        $this->expectException(DiscountException::class);
        ChargeService::recordPayment($op, 'fuel', 50.0, 'StoreCredit', $user->id);
    }

    // ── 24: Goodwill + General discount types remain representable ───────────

    public function test_goodwill_and_general_types_exist_but_are_phase1_inactive(): void
    {
        $this->assertFalse(\App\Enums\Discounts\DiscountType::Goodwill->isOperationalInPhase1());
        $this->assertFalse(\App\Enums\Discounts\DiscountType::General->isOperationalInPhase1());
        $this->assertTrue(\App\Enums\Discounts\DiscountType::StoreCredit->isOperationalInPhase1());
    }
}
