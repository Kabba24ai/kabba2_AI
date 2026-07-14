<?php

namespace Tests\Unit\Services;

use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Services\ReceiptService;
use Mockery;
use Tests\TestCase;

/**
 * Pure decision-table coverage for ReceiptService::currentPaymentStatusLabel()
 * against a mocked Order — isolates the branching logic itself (composition
 * of Order's existing canonical accessors: is_paid, total_paid,
 * total_refunded, balance_due, grand_total, last_payment_status) from any
 * database state. See ReceiptPaymentStatusTest for the full-stack,
 * real-database equivalent covering the same scenarios end to end.
 */
class ReceiptServiceLabelTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function mockOrder(array $attrs): Order
    {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldReceive('getIsPaidAttribute')->andReturn($attrs['is_paid'] ?? false);
        $order->shouldReceive('getTotalPaidAttribute')->andReturn($attrs['total_paid'] ?? 0.0);
        $order->shouldReceive('getTotalRefundedAttribute')->andReturn($attrs['total_refunded'] ?? 0.0);
        $order->shouldReceive('getBalanceDueAttribute')->andReturn($attrs['balance_due'] ?? 0.0);
        $order->shouldReceive('getLastPaymentStatusAttribute')->andReturn($attrs['last_payment_status'] ?? null);
        $order->grand_total = $attrs['grand_total'] ?? 0.0;

        return $order;
    }

    public function test_no_payment_yet_is_pending(): void
    {
        $order = $this->mockOrder(['grand_total' => 642.04]);
        $this->assertSame('Pending', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_is_paid_with_zero_balance_is_paid_in_full(): void
    {
        $order = $this->mockOrder([
            'is_paid' => true, 'total_paid' => 642.04, 'balance_due' => 0.0, 'grand_total' => 642.04,
        ]);
        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_overpaid_is_still_paid_in_full_not_mislabeled(): void
    {
        $order = $this->mockOrder([
            'is_paid' => true, 'total_paid' => 700.0, 'balance_due' => -57.96, 'grand_total' => 642.04,
        ]);
        $this->assertSame('Paid in Full', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_partial_payment_without_is_paid_is_partial(): void
    {
        $order = $this->mockOrder([
            'is_paid' => false, 'total_paid' => 75.0, 'balance_due' => 125.0, 'grand_total' => 200.0,
        ]);
        $this->assertSame('Partial Payment', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_fully_refunded_overrides_is_paid_never_shows_paid_in_full(): void
    {
        // is_paid stays true (the original Paid payment row is never mutated
        // by a refund — a new Refund-status row is created alongside it) —
        // total_refunded must still win over is_paid.
        $order = $this->mockOrder([
            'is_paid' => true, 'total_paid' => 150.0, 'total_refunded' => 150.0,
            'balance_due' => 0.0, 'grand_total' => 150.0,
        ]);
        $this->assertSame('Refunded', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_partial_refund_overrides_is_paid_never_shows_paid_in_full(): void
    {
        $order = $this->mockOrder([
            'is_paid' => true, 'total_paid' => 150.0, 'total_refunded' => 50.0,
            'balance_due' => 0.0, 'grand_total' => 150.0,
        ]);
        $this->assertSame('Partial Refund', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_voided_payment_reverts_to_pending(): void
    {
        // Void mutates the sole Paid row in place -> is_paid false, total_paid 0.
        $order = $this->mockOrder([
            'is_paid' => false, 'total_paid' => 0.0, 'balance_due' => 150.0, 'grand_total' => 150.0,
        ]);
        $this->assertSame('Pending', ReceiptService::currentPaymentStatusLabel($order));
    }

    public function test_failed_payment_with_no_successful_payment_shows_failed(): void
    {
        $order = $this->mockOrder([
            'is_paid' => false, 'total_paid' => 0.0, 'balance_due' => 100.0, 'grand_total' => 100.0,
            'last_payment_status' => OrderPaymentStatus::Failed->value,
        ]);
        $this->assertSame('Failed', ReceiptService::currentPaymentStatusLabel($order));
    }
}
