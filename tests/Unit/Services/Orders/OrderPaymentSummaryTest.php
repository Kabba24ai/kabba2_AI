<?php

namespace Tests\Unit\Services\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Orders\OrderPaymentSummary;
use Mockery;
use Tests\TestCase;

/**
 * Phase 3A — Payment Correctness Foundation.
 *
 * Pure-Unit coverage (no DB) for OrderPaymentSummary's own derivation
 * logic — collection/refund status as two separate dimensions (never
 * fused into one), and the "can this refund flow proceed without asking
 * which payment" guard the refund controller now enforces. Order's own
 * accessors (total_paid, total_refunded, is_paid, ...) are stubbed
 * directly here so this test is isolated to what OrderPaymentSummary
 * itself computes — those accessors have their own dedicated coverage in
 * OrderPaymentFormulasTest.
 */
class OrderPaymentSummaryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * @param  OrderPayment[]  $settledPayments
     */
    private function makeOrder(
        float $grandTotal,
        float $totalPaid,
        float $totalRefunded,
        bool $isPaid,
        array $settledPayments,
        array $remainingRefundableByPaymentId = [],
    ): Order {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->grand_total = $grandTotal;
        $order->shouldReceive('getTotalPaidAttribute')->andReturn($totalPaid);
        $order->shouldReceive('getTotalRefundedAttribute')->andReturn($totalRefunded);
        $order->shouldReceive('getIsPaidAttribute')->andReturn($isPaid);
        $order->shouldReceive('getNetPaidAttribute')->andReturn(max(0.0, $totalPaid - $totalRefunded));
        $order->shouldReceive('getBalanceDueAttribute')->andReturn(max(0.0, $grandTotal - $totalPaid));
        $order->shouldReceive('getRemainingAmountAttribute')->andReturn(max(0.0, $totalPaid - $totalRefunded));

        $paymentsQuery = Mockery::mock();
        $paymentsQuery->shouldReceive('settled')->andReturnSelf();
        $paymentsQuery->shouldReceive('orderBy')->andReturnSelf();
        $paymentsQuery->shouldReceive('get')->andReturn(collect($settledPayments));
        $order->shouldReceive('payments')->andReturn($paymentsQuery);

        foreach ($settledPayments as $payment) {
            $order->shouldReceive('remainingRefundableForPayment')
                ->with($payment)
                ->andReturn($remainingRefundableByPaymentId[$payment->id] ?? 0.0);
        }

        return $order;
    }

    private function makePayment(int $id, float $amount, OrderPaymentMethod $method): OrderPayment
    {
        $payment = new OrderPayment();
        $payment->id = $id;
        $payment->amount = $amount;
        $payment->payment_method = $method;
        $payment->status = OrderPaymentStatus::Paid;

        return $payment;
    }

    // ── Collection Status ────────────────────────────────────────────

    public function test_collection_status_unpaid_when_nothing_settled(): void
    {
        $order = $this->makeOrder(1000.0, 0.0, 0.0, false, []);

        $this->assertSame(OrderPaymentSummary::COLLECTION_UNPAID, $order->paymentSummary()->collectionStatus);
    }

    public function test_collection_status_partially_paid(): void
    {
        $payment = $this->makePayment(1, 400.0, OrderPaymentMethod::Cash);
        $order = $this->makeOrder(1000.0, 400.0, 0.0, false, [$payment], [1 => 400.0]);

        $this->assertSame(OrderPaymentSummary::COLLECTION_PARTIALLY_PAID, $order->paymentSummary()->collectionStatus);
    }

    public function test_collection_status_paid_in_full(): void
    {
        $payment = $this->makePayment(1, 1000.0, OrderPaymentMethod::Card);
        $order = $this->makeOrder(1000.0, 1000.0, 0.0, true, [$payment], [1 => 1000.0]);

        $this->assertSame(OrderPaymentSummary::COLLECTION_PAID_IN_FULL, $order->paymentSummary()->collectionStatus);
    }

    // ── Refund Status — kept as an INDEPENDENT dimension from Collection Status ──

    public function test_refund_status_none(): void
    {
        $payment = $this->makePayment(1, 1000.0, OrderPaymentMethod::Card);
        $order = $this->makeOrder(1000.0, 1000.0, 0.0, true, [$payment], [1 => 1000.0]);

        $this->assertSame(OrderPaymentSummary::REFUND_NONE, $order->paymentSummary()->refundStatus);
    }

    public function test_refund_status_partial_while_collection_status_stays_paid_in_full(): void
    {
        // Paid in Full AND Partially Refunded must be representable
        // simultaneously — this is exactly why the two are kept separate
        // rather than one fused status (mirrors the confirmed OrderPaymentStatus
        // Invoice* status/method fusion problem this phase deliberately avoids
        // repeating at the order level).
        $payment = $this->makePayment(1, 1000.0, OrderPaymentMethod::Card);
        $order = $this->makeOrder(1000.0, 1000.0, 200.0, true, [$payment], [1 => 800.0]);

        $summary = $order->paymentSummary();

        $this->assertSame(OrderPaymentSummary::COLLECTION_PAID_IN_FULL, $summary->collectionStatus);
        $this->assertSame(OrderPaymentSummary::REFUND_PARTIAL, $summary->refundStatus);
        $this->assertSame('Paid in Full · Partially Refunded', $summary->balanceStatusLabel());
    }

    public function test_refund_status_full(): void
    {
        $payment = $this->makePayment(1, 1000.0, OrderPaymentMethod::Card);
        $order = $this->makeOrder(1000.0, 1000.0, 1000.0, true, [$payment], [1 => 0.0]);

        $this->assertSame(OrderPaymentSummary::REFUND_FULL, $order->paymentSummary()->refundStatus);
    }

    // ── Refund-source ambiguity guard ────────────────────────────────

    public function test_has_unambiguous_refund_source_with_one_settled_payment(): void
    {
        $payment = $this->makePayment(1, 1000.0, OrderPaymentMethod::Card);
        $order = $this->makeOrder(1000.0, 1000.0, 0.0, true, [$payment], [1 => 1000.0]);

        $summary = $order->paymentSummary();

        $this->assertTrue($summary->hasUnambiguousRefundSource());
        $this->assertSame($payment, $summary->unambiguousRefundSource());
    }

    public function test_no_unambiguous_refund_source_with_multiple_settled_payments(): void
    {
        // The exact scenario RefundPaymentController now blocks rather
        // than silently guessing against (Order::lastPaidPayment) —
        // Phase 3B/3C scope, not this phase.
        $card = $this->makePayment(1, 600.0, OrderPaymentMethod::Card);
        $cash = $this->makePayment(2, 400.0, OrderPaymentMethod::Cash);
        $order = $this->makeOrder(1000.0, 1000.0, 0.0, true, [$card, $cash], [1 => 600.0, 2 => 400.0]);

        $summary = $order->paymentSummary();

        $this->assertFalse($summary->hasUnambiguousRefundSource());
        $this->assertNull($summary->unambiguousRefundSource());
    }

    // ── refundablePayments / paymentMethodsUsed ──────────────────────

    public function test_refundable_payments_excludes_fully_refunded_rows(): void
    {
        $card = $this->makePayment(1, 600.0, OrderPaymentMethod::Card);
        $cash = $this->makePayment(2, 400.0, OrderPaymentMethod::Cash);
        // Card fully refunded already (0 remaining); Cash still has $400 left.
        $order = $this->makeOrder(1000.0, 1000.0, 600.0, true, [$card, $cash], [1 => 0.0, 2 => 400.0]);

        $refundable = $order->paymentSummary()->refundablePayments;

        $this->assertCount(1, $refundable);
        $this->assertSame($cash, $refundable->first());
    }

    public function test_payment_methods_used_lists_distinct_methods(): void
    {
        $card = $this->makePayment(1, 600.0, OrderPaymentMethod::Card);
        $cash = $this->makePayment(2, 400.0, OrderPaymentMethod::Cash);
        $order = $this->makeOrder(1000.0, 1000.0, 0.0, true, [$card, $cash], [1 => 600.0, 2 => 400.0]);

        $methods = $order->paymentSummary()->paymentMethodsUsed;

        $this->assertCount(2, $methods);
        $this->assertTrue($methods->contains(OrderPaymentMethod::Card));
        $this->assertTrue($methods->contains(OrderPaymentMethod::Cash));
    }
}
