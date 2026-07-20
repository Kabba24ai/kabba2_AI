<?php

namespace Tests\Feature\QueueLine;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Livewire\QueueLine\Board;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\QueueLine\QueueLineEligibility;
use App\Services\QueueLine\QueueLineMobilePresenter;
use App\Services\QueueLine\QueueLineReleaseGuard;
use Livewire\Livewire;

/**
 * Active-order rule (refinement 2026-07-20): voided-out and fully refunded
 * orders are no longer active orders and must never appear on Queue Line.
 *
 * Parity with Schedule/Dispatch: those pages rely on
 * RefundedOrderScheduleCloser closing undelivered rows ('Close as
 * Completed' — terminal on both pages) on full refund, fee-retained
 * operationally-complete refund, and explicit void-and-cancel. Queue Line
 * honors that closure identically via delivery_status='Pending', and this
 * suite pins the additional financial-activity predicate that covers rows
 * the closure engine never touched — built from the SAME canonical
 * classifications (OrderPaymentSummary, isOperationallyCompleteRefund),
 * never a Queue-Line-specific approximation.
 */
class QueueLineFinancialEligibilityTest extends QueueLineTestCase
{
    private function pay(Order $order, float $amount, string $status = 'Paid'): OrderPayment
    {
        return $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => $amount,
            'status' => $status,
        ]);
    }

    /** Refund row shaped exactly like RefundPaymentController writes them. */
    private function refund(Order $order, OrderPayment $original, float $gross, float $feeRetained = 0.0): OrderPayment
    {
        $refund = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value,
            'payment_datetime' => now(),
            'amount' => 0,
            'status' => 'Refunded',
            'refund_amount' => $gross,
            'refunded_at' => now(),
        ]);

        $refund->refundAllocations()->create([
            'original_order_payment_id' => $original->id,
            'allocated_amount' => $gross,
            'allocated_base_amount' => $gross,
            'allocated_tax_amount' => 0,
            'processing_fee_retained' => $feeRetained > 0 ? $feeRetained : null,
            'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        return $refund;
    }

    private function boardHtml(): string
    {
        return Livewire::test(Board::class)->html();
    }

    // ── Exclusions ───────────────────────────────────────────────────────

    public function test_voided_out_order_never_appears_on_queue_line(): void
    {
        $row = $this->makeRow(); // queue-eligible (Pending, due today, Truck)
        $this->pay($row->order, 100, 'Voided');

        $this->assertStringNotContainsString('data-order-product-id="' . $row->id . '"', $this->boardHtml());
        $this->assertEmpty(QueueLineMobilePresenter::board()['items']);
        $this->assertSame(0, QueueLineMobilePresenter::summary()['total']);
    }

    public function test_fully_refunded_order_never_appears_on_queue_line(): void
    {
        $row = $this->makeRow();
        $original = $this->pay($row->order, 100);
        $this->refund($row->order->fresh(), $original, 100);

        $this->assertStringNotContainsString('data-order-product-id="' . $row->id . '"', $this->boardHtml());
        $this->assertEmpty(QueueLineMobilePresenter::board()['items']);
    }

    public function test_fee_retained_operationally_complete_refund_is_excluded_like_schedule_treats_it(): void
    {
        // "Full Amount Less Card Processing Fee": financially REFUND_PARTIAL
        // but the closure-equivalent state Schedule/Dispatch already treat
        // as inactive (RefundedOrderScheduleCloser::isOperationallyCompleteRefund).
        $row = $this->makeRow();
        $original = $this->pay($row->order, 100);
        $this->refund($row->order->fresh(), $original, 100, feeRetained: 3.00);

        $this->assertFalse(QueueLineEligibility::isOrderFinanciallyActive($row->order->fresh()));
        $this->assertStringNotContainsString('data-order-product-id="' . $row->id . '"', $this->boardHtml());
    }

    // ── Still active ─────────────────────────────────────────────────────

    public function test_partially_refunded_active_order_still_appears(): void
    {
        $row = $this->makeRow();
        $original = $this->pay($row->order, 100);
        $this->refund($row->order->fresh(), $original, 40);

        $this->assertTrue(QueueLineEligibility::isOrderFinanciallyActive($row->order->fresh()));
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $this->boardHtml());
    }

    public function test_void_then_recharge_order_stays_active(): void
    {
        // The correction workflow: the mistaken charge is voided, the
        // rental proceeds on the new settled payment.
        $row = $this->makeRow();
        $this->pay($row->order, 100, 'Voided');
        $this->pay($row->order->fresh(), 100, 'Paid');

        $this->assertTrue(QueueLineEligibility::isOrderFinanciallyActive($row->order->fresh()));
        $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $this->boardHtml());
    }

    public function test_unpaid_pending_and_partially_paid_orders_remain_eligible(): void
    {
        $unpaid = $this->makeRow();                       // no payment rows at all

        $pending = $this->makeRow();
        $this->pay($pending->order, 100, 'Pending');

        $partiallyPaid = $this->makeRow(
            $this->makeOrder(['grand_total' => 200])
        );
        $this->pay($partiallyPaid->order, 100, 'Partial Payment');

        $html = $this->boardHtml();
        foreach ([$unpaid, $pending, $partiallyPaid] as $row) {
            $this->assertStringContainsString('data-order-product-id="' . $row->id . '"', $html);
        }
    }

    // ── Guard parity: no ghost enforcement for invisible items ───────────

    public function test_release_guard_does_not_enforce_for_financially_inactive_orders(): void
    {
        $row = $this->makeRow();
        $this->softAssign($row); // staged, fuel NOT verified
        $this->pay($row->order, 100, 'Voided');

        // Active order in the same shape WOULD be blocked (fuel required)…
        $activeRow = $this->makeRow();
        $this->softAssign($activeRow);
        $this->assertNotNull(QueueLineReleaseGuard::check($activeRow->fresh(['softAssignment.equipment', 'queueLineItem', 'order'])));

        // …the voided-out one is outside Queue Line management entirely.
        $this->assertNull(QueueLineReleaseGuard::check($row->fresh(['softAssignment.equipment', 'queueLineItem', 'order'])));
    }
}
