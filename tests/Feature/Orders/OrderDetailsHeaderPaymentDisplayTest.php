<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Tier 2) — the Order Details page
 * header ("Paid In Full Via - ...", "Partial Payment: ...", "PENDING
 * PAYMENT", "PAYMENT FAILED") previously read Order::last_payment_type/
 * last_payment_status — the single most recent payment row — to describe
 * the WHOLE order. This renders the real edit.blade.php view (not a
 * re-implementation) for split-payment, partial-payment, and
 * failed-then-succeeded orders and asserts the corrected aggregate text
 * appears.
 */
class OrderDetailsHeaderPaymentDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }

        $this->customer = Customer::create([
            'first_name' => 'Header', 'last_name' => 'Display',
            'email' => 'header-display@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Header Display',
            'grand_total' => 1000,
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Header', 'last_name' => 'Clerk',
            'email' => 'header-display-clerk@example.com', 'status' => 'Active',
        ]));
    }

    private function renderEditPage()
    {
        return $this->get(route('admin.order-management.orders.edit', $this->order->fresh()->unique_id))->assertOk();
    }

    public function test_split_payment_paid_order_shows_multiple_methods_not_just_the_last_one(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 600, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Paid In Full Via -');
        $response->assertSee('Multiple Methods');
    }

    public function test_single_payment_order_still_shows_its_one_method(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Paid In Full Via -');
        $response->assertSee('Cash');
        $response->assertDontSee('Multiple Methods');
    }

    public function test_partially_paid_order_shows_the_partial_payment_badge_regardless_of_which_row_is_last(): void
    {
        // Partial-status row entered FIRST, an unrelated later row (e.g. a
        // failed retry) entered after it. The OLD code gated this badge on
        // Order::last_payment_status === PartialPayment specifically.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 300, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Partial Payment:');
        $response->assertSee('PAYMENT FAILED');
    }

    public function test_failed_then_succeeded_order_shows_paid_in_full_and_not_payment_failed(): void
    {
        // Status precedence hardening: an earlier failed attempt must stop
        // driving the order-level badge once a later payment genuinely
        // satisfies the balance — it remains in payment history (see
        // OrderPaymentSummary::pendingOrFailedPayments) but must not keep
        // presenting the order itself as PAYMENT FAILED.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Paid In Full Via -');
        $response->assertDontSee('PAYMENT FAILED');
        $response->assertDontSee('PENDING PAYMENT');
    }

    public function test_fully_paid_then_partially_refunded_order_does_not_show_plain_paid_in_full(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'refund_amount' => 250,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Paid in Full');
        $response->assertSee('Partially Refunded');
        $response->assertDontSee('Paid In Full Via -');
    }

    public function test_fully_paid_then_fully_refunded_order_shows_refunded_not_paid_in_full(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'refund_amount' => 1000,
            'status' => OrderPaymentStatus::Refund->value,
        ]);

        $response = $this->renderEditPage();

        $response->assertSee('Fully Refunded');
        $response->assertDontSee('Paid In Full Via -');
    }
}
