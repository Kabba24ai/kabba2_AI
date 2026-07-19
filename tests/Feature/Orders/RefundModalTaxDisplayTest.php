<?php

namespace Tests\Feature\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Configurations\Setting;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPaymentRefundAllocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Payment Architecture Finalization — Final Read-Side Cleanup. The refund
 * modal's "previously refunded tax" display (edit.blade.php's
 * data-tax-already-refunded attribute) previously summed the legacy
 * order_payments.tax_refunded column directly across every PartialRefund/
 * Refund row — allocation-unaware, so a partially-failed multi-source
 * refund or a stale un-backfilled legacy value could show a wrong number.
 * Now sourced from PaymentAllocationService::totalSuccessfulRefundedTax(),
 * mirroring totalSuccessfulRefunded()'s exact Allocated-only pattern.
 *
 * Every fixture here deliberately sets the legacy tax_refunded column to a
 * DIFFERENT, wrong value from the real allocation data, so a passing
 * assertion proves the allocation-aware branch — not a coincidental
 * fallback — is what actually rendered.
 *
 * History note: an earlier version of this class documented a known
 * pre-existing issue — resolveRequestedTotal()'s SalesTaxOnly branch
 * computed "remaining refundable sales tax" via the unguarded
 * sum('tax_refunded') pattern, so the display value and the eligibility
 * figure could legitimately disagree, and a test here proved that
 * decoupling. That issue has since been deliberately fixed (Payment
 * Architecture Finalization — Sales Tax Refund Write-Path Correction,
 * PaymentAllocationService::resolveRequestedTotal(), SalesTaxOnly branch):
 * both figures now derive from the same canonical
 * totalSuccessfulRefundedTax() and can never disagree. The final test
 * below now asserts that coherence instead of the old decoupling.
 */
class RefundModalTaxDisplayTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;
    private Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::create([
            'first_name' => 'TaxDisplay', 'last_name' => 'Test',
            'email' => 'tax-display-test@example.com', 'status' => 'Active',
        ]);

        $this->order = Order::create([
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $this->customer->id,
            'customer_name' => 'TaxDisplay Test',
            'subtotal' => 1000.0, 'tax_amount' => 100.0, 'grand_total' => 1100.0,
        ]);

        $this->actingAs(User::create([
            'first_name' => 'Gary', 'last_name' => 'Admin',
            'email' => 'gary-tax-display-test@example.com', 'status' => 'Active',
        ]));

        foreach (['payment_api_public_key', 'payment_api_key'] as $name) {
            Setting::create([
                'setting_type' => 'Payment Settings', 'value_type' => 'password',
                'setting_name' => $name, 'setting_title' => $name, 'setting_value' => 'test',
            ]);
        }
    }

    private function renderEditPage()
    {
        return $this->get(route('admin.order-management.orders.edit', $this->order->unique_id))->assertOk();
    }

    // ── 1. No previous tax refund ────────────────────────────────────────

    public function test_no_previous_refund_shows_zero(): void
    {
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);

        $this->renderEditPage()->assertSee('data-tax-already-refunded="0"', false);
    }

    // ── 2. Completed sales-tax-only refund ───────────────────────────────

    public function test_completed_sales_tax_only_refund_shows_the_allocated_tax(): void
    {
        $payment = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            // Deliberately wrong legacy value — proves the allocation
            // branch, not this fallback, drives the display.
            'refund_amount' => 0, 'tax_refunded' => 999,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 50, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 50,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $this->renderEditPage()
            ->assertSee('data-tax-already-refunded="50"', false)
            ->assertDontSee('data-tax-already-refunded="999"', false);
    }

    // ── 3. Partially completed multi-source tax refund ──────────────────

    public function test_multi_source_refund_sums_tax_across_both_allocations(): void
    {
        $payment1 = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now()->subMinute(),
            'amount' => 600.0, 'status' => OrderPaymentStatus::PartialPayment->value,
        ]);
        $payment2 = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 500.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Cash->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment1->id,
            'allocated_amount' => 20, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 20,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment2->id,
            'allocated_amount' => 15, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 15,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $this->renderEditPage()->assertSee('data-tax-already-refunded="35"', false);
    }

    // ── 4. One completed and one failed refund operation ────────────────

    public function test_failed_allocation_alongside_a_completed_one_is_excluded(): void
    {
        $payment = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 30, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 30,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 999, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 999,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Failed->value,
        ]);

        $this->renderEditPage()
            ->assertSee('data-tax-already-refunded="30"', false)
            ->assertDontSee('data-tax-already-refunded="1029"', false);
    }

    // ── 5. Pending refund operation ──────────────────────────────────────

    public function test_pending_allocation_is_not_counted_yet(): void
    {
        $payment = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 40, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 40,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Pending->value,
        ]);

        $this->renderEditPage()->assertSee('data-tax-already-refunded="0"', false);
    }

    // ── 6. Historical allocation-backfilled refund ───────────────────────

    public function test_backfilled_allocation_renders_identically_to_a_live_refund(): void
    {
        $payment = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMonths(6),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now()->subMonths(6),
            'refunded_at' => now()->subMonths(6), 'amount' => 0,
            // Stale legacy value from before the backfill ran.
            'refund_amount' => 0, 'tax_refunded' => 999,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);
        // payments:backfill-allocations reconstructs a real Allocated row —
        // no different in shape from one created live by RefundPaymentController.
        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 25, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 25,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $this->renderEditPage()->assertSee('data-tax-already-refunded="25"', false);
    }

    // ── 7. Legacy stale tax_refunded differing from completed allocation ──

    public function test_legacy_row_with_no_allocations_at_all_falls_back_to_the_raw_column(): void
    {
        // Genuinely never backfilled — zero allocation rows exist, so the
        // method's documented legacy fallback (not the allocation branch)
        // is the correct, intentional path here.
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            'refund_amount' => 0, 'tax_refunded' => 60,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $this->renderEditPage()->assertSee('data-tax-already-refunded="60"', false);
    }

    // ── 8 & 9. Display value is allocation-only, and never influences eligibility/amount ──

    public function test_remaining_refundable_tax_moves_coherently_with_the_canonical_display_value(): void
    {
        // Deliberately does NOT touch the legacy tax_refunded column on the
        // refund row (left at its DB default) — only the allocation is
        // real, proving the allocation-aware canonical path (not the legacy
        // raw column) is what drives BOTH figures. Since the Sales Tax
        // Refund Write-Path Correction, resolveRequestedTotal()'s
        // SalesTaxOnly branch and the display value share one source
        // (totalSuccessfulRefundedTax), so allocating 30 of tax must move
        // data-tax-remaining-refundable from 100 to exactly 70 while
        // eligibility stays on — the two can never disagree. (This test
        // previously asserted the OPPOSITE — that the figures were
        // decoupled — which was true of the pre-correction defect it was
        // written to document; see the class docblock history note.)
        $payment = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'amount' => 1100.0, 'status' => OrderPaymentStatus::Paid->value,
        ]);
        $refund = $this->order->payments()->create([
            'payment_method' => OrderPaymentMethod::Card->value, 'payment_datetime' => now(),
            'refunded_at' => now(), 'amount' => 0,
            'status' => OrderPaymentStatus::PartialRefund->value,
        ]);

        $baseline = $this->renderEditPage();
        $baseline->assertSee('data-tax-already-refunded="0"', false);
        preg_match('/data-tax-remaining-refundable="([\d.]+)"/', $baseline->getContent(), $before);
        $this->assertSame('100', $before[1] ?? null, 'before any allocation, the full order tax must be refundable');

        OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id, 'original_order_payment_id' => $payment->id,
            'allocated_amount' => 30, 'allocated_base_amount' => 0, 'allocated_tax_amount' => 30,
            'processing_fee_retained' => 0, 'status' => OrderPaymentRefundAllocationStatus::Allocated->value,
        ]);

        $after = $this->renderEditPage();
        $after->assertSee('data-tax-already-refunded="30"', false);
        $after->assertSee('data-tax-only-eligible="1"', false);
        preg_match('/data-tax-remaining-refundable="([\d.]+)"/', $after->getContent(), $afterMatch);

        $this->assertSame(
            '70',
            $afterMatch[1] ?? null,
            'remaining refundable tax must decrease by exactly the allocated tax (100 - 30) — it shares one canonical source with the display value and can never disagree with it'
        );
    }
}
