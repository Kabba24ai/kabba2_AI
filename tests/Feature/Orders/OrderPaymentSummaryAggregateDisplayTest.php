<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Helpers\CustomHelper;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\OrderPaymentSummary;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Tier 2) — the canonical-layer additions
 * that back every corrected operational badge/label: OrderPaymentSummary's
 * pendingOrFailedPayments field, and PaymentDescriptionPresenter's
 * orderStatusLabel()/orderStatusBadgeClasses() (and CustomHelper's HTML
 * wrapper), which describe the ORDER's aggregate state rather than a single
 * payment row.
 */
class OrderPaymentSummaryAggregateDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Aggregate', 'last_name' => 'Display',
            'email' => 'aggregate-display@example.com', 'status' => 'Active',
        ]);
    }

    private function makeOrder(float $grandTotal): Order
    {
        return Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Aggregate Display',
            'grand_total' => $grandTotal,
        ]);
    }

    public function test_pending_or_failed_payments_includes_an_earlier_failed_row_even_after_a_later_success(): void
    {
        $order = $this->makeOrder(500);

        // Failed attempt first...
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 500, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        // ...then a later, successful retry. Order::last_payment_status
        // would now read "Paid" — hiding that an earlier attempt failed.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order);

        $this->assertCount(1, $summary->pendingOrFailedPayments);
        $this->assertSame(OrderPaymentStatus::Failed, $summary->pendingOrFailedPayments->first()->status);
        $this->assertTrue((bool) $order->is_paid, 'the order is genuinely fully paid despite the earlier failed attempt');
    }

    public function test_order_status_label_reflects_partial_refund_not_the_original_paid_status(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 200,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertSame('Paid in Full · Partially Refunded', PaymentDescriptionPresenter::orderStatusLabel($summary));
        $this->assertSame('bg-orange-100 text-orange-800', PaymentDescriptionPresenter::orderStatusBadgeClasses($summary));
    }

    public function test_order_status_label_for_a_fully_refunded_order(): void
    {
        $order = $this->makeOrder(500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'refunded_at' => now(),
            'amount' => 0, 'refund_amount' => 500,
            'status' => OrderPaymentStatus::Refund->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertSame('Paid in Full · Fully Refunded', PaymentDescriptionPresenter::orderStatusLabel($summary));
        $this->assertSame('bg-purple-100 text-purple-800', PaymentDescriptionPresenter::orderStatusBadgeClasses($summary));
    }

    public function test_order_payment_status_badge_renders_the_aggregate_label_as_html(): void
    {
        $order = $this->makeOrder(300);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 150, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());
        $html = CustomHelper::orderPaymentStatusBadge($summary);

        $this->assertStringContainsString('Partially Paid', $html);
        $this->assertStringContainsString('bg-orange-100', $html);
    }

    public function test_methods_used_reflects_every_settled_method_not_just_the_last(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now()->subMinute(),
            'amount' => 600, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value,
            'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());
        $label = PaymentDescriptionPresenter::methodsUsedLabel($summary->paymentMethodsUsed);

        $this->assertSame('Multiple Methods', $label);
        $this->assertCount(2, $summary->paymentMethodsUsed);
    }
}
