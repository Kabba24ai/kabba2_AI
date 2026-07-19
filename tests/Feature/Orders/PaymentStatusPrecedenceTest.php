<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Customers\Customer;
use App\Models\Orders\Order;
use App\Services\Orders\OrderPaymentSummary;
use App\Services\PaymentDescriptionPresenter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization (Tier 2 hardening) — status precedence.
 *
 * OrderPaymentSummary::pendingOrFailedPayments is the complete HISTORICAL
 * record of every Pending/Failed original payment row, and must remain
 * available for audit purposes even after an order is resolved.
 * unresolvedPaymentAttempts is the subset that still represents an ACTIVE
 * problem — empty the moment the order is genuinely fully paid by any
 * combination of rows, regardless of how many Pending/Failed rows exist in
 * its history. Every "needs attention" consumer (Order Details header
 * today) must key off unresolvedPaymentAttempts, never
 * pendingOrFailedPayments directly — this file pins that distinction down
 * for the ten scenarios the Tier 2 review named explicitly.
 */
class PaymentStatusPrecedenceTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'Precedence', 'last_name' => 'Test',
            'email' => 'precedence-test@example.com', 'status' => 'Active',
        ]);
    }

    private function makeOrder(float $grandTotal): Order
    {
        return Order::create([
            'order_date' => now()->toDateString(),
            'customer_id' => $this->customer->id,
            'customer_name' => 'Precedence Test',
            'grand_total' => $grandTotal,
        ]);
    }

    // ── 1. Failed Card attempt, then successful Cash payment in full ──────

    public function test_failed_card_then_successful_cash_in_full_is_paid_in_full_not_failed(): void
    {
        $order = $this->makeOrder(500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertCount(0, $summary->unresolvedPaymentAttempts, 'the failed attempt must not remain active once the order is fully paid');
        $this->assertCount(1, $summary->pendingOrFailedPayments, 'but it must remain in the historical record');
        $this->assertSame('Paid in Full', PaymentDescriptionPresenter::orderStatusLabel($summary));
    }

    // ── 2. Failed Card attempt, then successful split Cash/Card in full ───

    public function test_failed_card_then_successful_split_payment_in_full_is_paid_in_full_not_failed(): void
    {
        $order = $this->makeOrder(500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinutes(2),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 300, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 200, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertCount(0, $summary->unresolvedPaymentAttempts);
        $this->assertSame('Multiple Methods', PaymentDescriptionPresenter::methodsUsedLabel($summary->paymentMethodsUsed));
        $this->assertSame('Paid in Full', PaymentDescriptionPresenter::orderStatusLabel($summary));
    }

    // ── 3. Pending COD placeholder, then completed payment ────────────────

    public function test_pending_cod_placeholder_updated_in_place_leaves_no_unresolved_attempt(): void
    {
        $order = $this->makeOrder(200);
        $placeholder = $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 200, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        // Real-flow conversion: the placeholder row is updated in place (see
        // PaymentStoreController::handleExtensionPayment / ReceivePaymentController).
        $placeholder->update(['payment_method' => OrderPaymentMethod::Cash->value, 'status' => OrderPaymentStatus::Paid->value]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertCount(0, $summary->unresolvedPaymentAttempts);
        $this->assertCount(0, $summary->pendingOrFailedPayments, 'updated in place — no separate stale row remains');
    }

    public function test_abandoned_cod_placeholder_left_dangling_becomes_historical_once_a_separate_payment_settles_the_order(): void
    {
        $order = $this->makeOrder(200);
        // Placeholder left dangling (NOT updated in place) — a genuinely
        // separate payment settles the order instead.
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 200, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 200, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertCount(0, $summary->unresolvedPaymentAttempts, 'the abandoned/superseded pending row must not override the later completed payment');
        $this->assertCount(1, $summary->pendingOrFailedPayments, 'still visible historically');
    }

    // ── 4. Partial payment, then failed attempt, balance still due ────────

    public function test_partial_payment_then_failed_attempt_with_balance_due_shows_both(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 400, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertFalse((bool) $order->fresh()->is_paid);
        $this->assertSame(OrderPaymentSummary::COLLECTION_PARTIALLY_PAID, $summary->collectionStatus);
        $this->assertCount(1, $summary->unresolvedPaymentAttempts, 'the failed attempt is still active — balance remains due');
        $this->assertSame(600.0, round((float) $order->fresh()->balance_due, 2));
    }

    // ── 5. Failed attempt, no successful payment, full balance due ────────

    public function test_failed_attempt_with_no_successful_payment_is_unresolved(): void
    {
        $order = $this->makeOrder(750);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertSame(OrderPaymentSummary::COLLECTION_UNPAID, $summary->collectionStatus);
        $this->assertCount(1, $summary->unresolvedPaymentAttempts);
        $this->assertTrue($summary->unresolvedPaymentAttempts->contains(fn ($p) => $p->status === OrderPaymentStatus::Failed));
    }

    // ── 6. Fully paid, then partially refunded ─────────────────────────────

    public function test_fully_paid_then_partially_refunded_shows_partial_refund_not_paid_in_full(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 1000, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'refund_amount' => 200,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertSame(OrderPaymentSummary::REFUND_PARTIAL, $summary->refundStatus);
        $this->assertSame('Paid in Full · Partially Refunded', PaymentDescriptionPresenter::orderStatusLabel($summary));
        $this->assertNotSame('Paid in Full', PaymentDescriptionPresenter::orderStatusLabel($summary));
    }

    // ── 7. Fully paid, then fully refunded ─────────────────────────────────

    public function test_fully_paid_then_fully_refunded_shows_refunded_not_paid_in_full(): void
    {
        $order = $this->makeOrder(500);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0, 'refund_amount' => 500,
            'status' => OrderPaymentStatus::Refund->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertSame(OrderPaymentSummary::REFUND_FULL, $summary->refundStatus);
        $this->assertSame('Paid in Full · Fully Refunded', PaymentDescriptionPresenter::orderStatusLabel($summary));
    }

    // ── 8. Multiple historical failed attempts, then a successful payment ─

    public function test_multiple_historical_failed_attempts_then_success_are_all_historical(): void
    {
        $order = $this->makeOrder(300);
        foreach (range(1, 3) as $i) {
            $order->payments()->create([
                'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinutes(10 - $i),
                'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
            ]);
        }
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'amount' => 300, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertCount(3, $summary->pendingOrFailedPayments, 'all three remain in history');
        $this->assertCount(0, $summary->unresolvedPaymentAttempts, 'none remain active — the order is fully paid');
    }

    // ── 9. Multiple successful methods plus one failed attempt ────────────

    public function test_multiple_successful_methods_plus_one_failed_attempt_is_paid_in_full_multiple_methods(): void
    {
        $order = $this->makeOrder(1000);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMinutes(3),
            'amount' => 0, 'status' => OrderPaymentStatus::Failed->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinutes(2),
            'amount' => 500, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cheque->value, 'payment_datetime' => now(),
            'amount' => 500, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertCount(0, $summary->unresolvedPaymentAttempts);
        $this->assertSame('Multiple Methods', PaymentDescriptionPresenter::methodsUsedLabel($summary->paymentMethodsUsed));
        $this->assertCount(1, $summary->pendingOrFailedPayments);
    }

    // ── 10. Active pending payment vs. an old superseded pending row ──────

    public function test_active_pending_payment_on_an_unpaid_order_remains_unresolved(): void
    {
        $order = $this->makeOrder(400);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Pending->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertCount(1, $summary->unresolvedPaymentAttempts, 'a genuinely still-open pending payment is active, not historical');
    }

    public function test_old_superseded_pending_row_does_not_override_a_later_completed_payment(): void
    {
        $order = $this->makeOrder(400);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::COD->value, 'payment_datetime' => now()->subHour(),
            'amount' => 400, 'status' => OrderPaymentStatus::Pending->value,
        ]);
        $order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 400, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $summary = OrderPaymentSummary::for($order->fresh());

        $this->assertTrue((bool) $order->fresh()->is_paid);
        $this->assertCount(0, $summary->unresolvedPaymentAttempts, 'the superseded pending row must not keep the order showing as pending');
        $this->assertSame('Paid in Full', PaymentDescriptionPresenter::orderStatusLabel($summary));
    }
}
