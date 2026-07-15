<?php

namespace Tests\Unit\Models\Orders;

use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use Tests\TestCase;

/**
 * Phase 3A — Payment Correctness Foundation.
 *
 * Pure-Unit coverage (no DB — Mockery partial mocks) for the two confirmed
 * defects this phase fixes on the Order model, plus the new canonical
 * formulas built on top of them:
 *
 *   1. is_paid must be derived from the aggregate settled-payments total
 *      against grand_total, not from any single payments() row
 *      individually carrying status Paid.
 *   2. remaining_amount (the refund cap) must be capped by what was
 *      actually collected (total_paid - total_refunded), not by
 *      grand_total - total_refunded, which is wrong for a partially-paid
 *      order.
 */
class OrderPaymentFormulasTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Builds a partial-mocked Order with grand_total set as a real
     * attribute (no mutator conflict) and total_paid/total_refunded
     * overridden directly — isolates the formulas under test from the
     * payments()->settled()->sum() query chain those two accessors
     * themselves rely on (covered separately, at the scope level, is out
     * of reach without a real DB — these two are the seams every other
     * formula in this test composes from).
     */
    private function makeOrder(float $grandTotal, float $totalPaid, float $totalRefunded): Order
    {
        $order = Mockery::mock(Order::class)->makePartial();
        $order->shouldAllowMockingProtectedMethods();
        $order->grand_total = $grandTotal;
        $order->shouldReceive('getTotalPaidAttribute')->andReturn($totalPaid);
        $order->shouldReceive('getTotalRefundedAttribute')->andReturn($totalRefunded);

        return $order;
    }

    // ── is_paid: two partial payments summing to the full total ────────

    public function test_is_paid_true_when_two_partial_payments_together_equal_grand_total(): void
    {
        // $500 Cash + $500 Card on a $1,000 order — neither row is itself
        // status Paid, but together they satisfy the order. The old
        // implementation (payments()->where('status','Paid')->exists())
        // never flipped true for this case.
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 1000.0, totalRefunded: 0.0);

        $this->assertTrue($order->is_paid);
    }

    public function test_is_paid_false_when_partially_paid(): void
    {
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 400.0, totalRefunded: 0.0);

        $this->assertFalse($order->is_paid);
    }

    public function test_is_paid_true_with_rounding_epsilon(): void
    {
        // Floating-point settlement landing a hair under grand_total must
        // still read as paid — same epsilon convention already used by
        // ReceivePaymentController's own partial-completes-total check.
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 999.998, totalRefunded: 0.0);

        $this->assertTrue($order->is_paid);
    }

    // ── remaining_amount (order refundable balance): the confirmed-bug fix ──

    public function test_remaining_amount_capped_by_total_paid_not_grand_total_on_partial_payment(): void
    {
        // The confirmed bug: a $1,000 order with only $400 ever collected
        // must never allow refunding more than $400. The old formula
        // (grand_total - total_refunded = 1000 - 0 = 1000) would have
        // permitted a $1,000 refund against $400 of real money.
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 400.0, totalRefunded: 0.0);

        $this->assertSame(400.0, $order->remaining_amount);
    }

    public function test_remaining_amount_on_fully_paid_order_matches_old_formula(): void
    {
        // On a fully-paid order the old and new formulas coincide
        // (total_paid == grand_total) — this is why the bug was masked;
        // confirm no regression for the common case.
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 1000.0, totalRefunded: 0.0);

        $this->assertSame(1000.0, $order->remaining_amount);
    }

    public function test_remaining_amount_reduced_by_prior_refunds(): void
    {
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 1000.0, totalRefunded: 200.0);

        $this->assertSame(800.0, $order->remaining_amount);
    }

    public function test_remaining_amount_never_negative(): void
    {
        // Defensive: refunds should never exceed total_paid in practice,
        // but the formula itself must not produce a negative cap.
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 400.0, totalRefunded: 400.0);

        $this->assertSame(0.0, $order->remaining_amount);
    }

    // ── net_paid: same formula as remaining_amount, different purpose ──

    public function test_net_paid_matches_remaining_amount_formula(): void
    {
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 1000.0, totalRefunded: 200.0);

        $this->assertSame(800.0, $order->net_paid);
        $this->assertSame($order->remaining_amount, $order->net_paid);
    }

    // ── balance_due: unchanged formula, still correct ───────────────────

    public function test_balance_due_unaffected_by_refunds(): void
    {
        // A refund does not make the customer owe the order again — that
        // is a deliberately separate dimension (Phase 3 payment
        // allocation architecture doc, §6/§9).
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 1000.0, totalRefunded: 200.0);

        $this->assertSame(0.0, $order->balance_due);
    }

    public function test_balance_due_reflects_partial_payment(): void
    {
        $order = $this->makeOrder(grandTotal: 1000.0, totalPaid: 400.0, totalRefunded: 0.0);

        $this->assertSame(600.0, $order->balance_due);
    }

    // ── remainingRefundableForPayment(): the per-payment cap ────────────

    /**
     * Phase 3B: remainingRefundableForPayment() now delegates to
     * PaymentAllocationService::remainingRefundable(), which checks
     * receivedRefundAllocations()->exists() first and only falls back to
     * childRefunds() when no allocation rows exist yet for this payment.
     * Mocking receivedRefundAllocations() to report none keeps this helper
     * exercising exactly the legacy-fallback formula it did in Phase 3A —
     * the allocation-aware path is covered in the Feature test suite.
     */
    private function makePaymentWithChildRefunds(float $amount, OrderPaymentStatus $status, float $alreadyRefunded): OrderPayment
    {
        $payment = Mockery::mock(OrderPayment::class)->makePartial();
        $payment->amount = $amount;
        $payment->status = $status;

        $childRefundsQuery = Mockery::mock(HasMany::class);
        $childRefundsQuery->shouldReceive('whereIn')->andReturnSelf();
        $childRefundsQuery->shouldReceive('sum')->andReturn($alreadyRefunded);
        $payment->shouldReceive('childRefunds')->andReturn($childRefundsQuery);

        $receivedAllocationsQuery = Mockery::mock(HasMany::class);
        $receivedAllocationsQuery->shouldReceive('exists')->andReturn(false);
        $payment->shouldReceive('receivedRefundAllocations')->andReturn($receivedAllocationsQuery);

        return $payment;
    }

    public function test_remaining_refundable_for_payment_with_no_prior_refunds(): void
    {
        $order = Mockery::mock(Order::class)->makePartial();
        $payment = $this->makePaymentWithChildRefunds(600.0, OrderPaymentStatus::Paid, 0.0);

        $this->assertSame(600.0, $order->remainingRefundableForPayment($payment));
    }

    public function test_remaining_refundable_for_payment_reduced_by_prior_child_refunds(): void
    {
        $order = Mockery::mock(Order::class)->makePartial();
        $payment = $this->makePaymentWithChildRefunds(600.0, OrderPaymentStatus::Paid, 250.0);

        $this->assertSame(350.0, $order->remainingRefundableForPayment($payment));
    }

    public function test_remaining_refundable_for_payment_is_zero_when_voided(): void
    {
        // A voided payment is immediately non-refundable — it was already
        // reversed at the gateway.
        $order = Mockery::mock(Order::class)->makePartial();
        $payment = $this->makePaymentWithChildRefunds(600.0, OrderPaymentStatus::Voided, 0.0);

        $this->assertSame(0.0, $order->remainingRefundableForPayment($payment));
    }

    public function test_remaining_refundable_for_payment_never_negative(): void
    {
        $order = Mockery::mock(Order::class)->makePartial();
        $payment = $this->makePaymentWithChildRefunds(600.0, OrderPaymentStatus::Paid, 600.0);

        $this->assertSame(0.0, $order->remainingRefundableForPayment($payment));
    }
}
