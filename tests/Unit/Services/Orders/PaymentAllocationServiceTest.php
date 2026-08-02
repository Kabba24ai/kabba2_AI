<?php

namespace Tests\Unit\Services\Orders;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Enums\Orders\RefundOperationStatus;
use App\Http\DataObjects\HistoricalTaxBasis;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Services\Orders\PaymentAllocationService;
use Illuminate\Support\Collection;
use Tests\TestCase;

/**
 * Phase 3B — Refund Allocation Foundation. Extended in Phase 3C —
 * Employee-Selected Refund Sources and Multi-Source Refund Processing.
 *
 * Genuinely executable, DB-free Unit tests. PaymentAllocationService's
 * remaining methods (remainingRefundable(), resolveLegacyOriginalPayment(),
 * attributionState(), backfill(), validateAllocationSet(),
 * remainingCardFeeCapacity(), orderHasAmbiguousRefundAttribution(),
 * suggestAllocation(), eligibleOriginalPayments(), totalSuccessfulRefunded(),
 * syncRefundOperationOutcome()) all query relations and therefore require a
 * real database connection — those are covered by
 * tests/Feature/Orders/PaymentAllocationFoundationTest.php and
 * tests/Feature/Orders/MultiSourceRefundTest.php instead (written but not
 * executed in this sandbox; see those files' docblocks).
 *
 * What IS tested here without a database:
 *  - validateSplit()'s pure arithmetic, including the fee-inclusive
 *    invariant (base + tax + fee == amount) added in Phase 3C and the
 *    integer-cents comparison that avoids float-precision false
 *    positives/negatives.
 *  - allocateSingleSource()'s two order/self guard clauses, both of which
 *    throw BEFORE any query or write — constructed with real (unsaved)
 *    OrderPayment instances, never persisted.
 *  - calculateAllocationSplits() for Standard and Sales Tax Only — neither
 *    branch touches the database (only Card Processing Fee Retained calls
 *    remainingCardFeeCapacity(), which queries a relation) — built with
 *    real, unsaved Order instances plus an explicitly-supplied
 *    HistoricalTaxBasis. The basis must now be passed in: since the
 *    denominator correction, proportionalTaxRefund() sources its rate from
 *    HistoricalTaxBasisResolver, which reads persisted order_products rows
 *    that an unsaved Order does not have. Supplying it keeps these tests
 *    DB-free and focused on the extraction arithmetic; the resolver's own
 *    reconstruction and rejection behavior is covered against real rows in
 *    tests/Feature/Orders/HistoricalTaxBasisResolverTest.php, and the
 *    corrected end-to-end refund behavior in
 *    tests/Feature/Orders/RefundTaxBasisRegressionTest.php.
 *  - proportionalTaxRefund()'s pure formula.
 *  - OrderPaymentRefundAllocationStatus::reservesBalance() and
 *    RefundOperationStatus::isRetryable().
 */
class PaymentAllocationServiceTest extends TestCase
{
    // ── validateSplit() ─────────────────────────────────────────────

    public function test_validate_split_accepts_a_split_that_sums_correctly(): void
    {
        PaymentAllocationService::validateSplit(100.00, 90.91, 9.09);
        $this->addToAssertionCount(1); // no exception thrown
    }

    public function test_validate_split_accepts_a_zero_base_full_tax_split(): void
    {
        // Sales Tax Only shape: base = 0, tax = full amount.
        PaymentAllocationService::validateSplit(50.00, 0.00, 50.00);
        $this->addToAssertionCount(1);
    }

    public function test_validate_split_rejects_a_mismatched_split(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PaymentAllocationService::validateSplit(100.00, 90.00, 9.00);
    }

    public function test_validate_split_is_immune_to_binary_floating_point_noise(): void
    {
        // 0.1 + 0.2 !== 0.3 in raw float arithmetic; the integer-cents
        // comparison inside validateSplit() must not be fooled by this.
        PaymentAllocationService::validateSplit(0.3, 0.1, 0.2);
        $this->addToAssertionCount(1);
    }

    public function test_validate_split_still_rejects_a_genuine_one_cent_imbalance(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        PaymentAllocationService::validateSplit(100.00, 90.90, 9.09);
    }

    // ── allocateSingleSource() guard clauses (no DB reached) ─────────

    public function test_allocate_single_source_rejects_refund_and_original_from_different_orders(): void
    {
        $refund = new OrderPayment(['order_id' => 1]);
        $refund->id = 10;

        $original = new OrderPayment(['order_id' => 2]);
        $original->id = 5;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must belong to the same order');

        PaymentAllocationService::allocateSingleSource($refund, $original, 100.0, 100.0, 0.0);
    }

    public function test_allocate_single_source_rejects_a_refund_allocated_against_itself(): void
    {
        $payment = new OrderPayment(['order_id' => 1]);
        $payment->id = 7;

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('cannot be allocated against itself');

        PaymentAllocationService::allocateSingleSource($payment, $payment, 100.0, 100.0, 0.0);
    }

    public function test_allocate_single_source_validates_the_split_before_any_guard_specific_to_order_matching(): void
    {
        $refund = new OrderPayment(['order_id' => 1]);
        $refund->id = 10;

        $original = new OrderPayment(['order_id' => 2]);
        $original->id = 5;

        // Even with a bad split, the order-mismatch guard (checked first)
        // must be the one that fires — asserted via the message.
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must belong to the same order');

        PaymentAllocationService::allocateSingleSource($refund, $original, 100.0, 1.0, 1.0);
    }

    // ── OrderPaymentRefundAllocationStatus::reservesBalance() ────────

    public function test_pending_and_allocated_reserve_balance_failed_does_not(): void
    {
        $this->assertTrue(OrderPaymentRefundAllocationStatus::Pending->reservesBalance());
        $this->assertTrue(OrderPaymentRefundAllocationStatus::Allocated->reservesBalance());
        $this->assertFalse(OrderPaymentRefundAllocationStatus::Failed->reservesBalance());
    }

    // ── Phase 3C: validateSplit() fee-inclusive invariant ────────────

    public function test_validate_split_accepts_a_gross_split_that_includes_a_fee(): void
    {
        // Card Processing Fee Retained shape: base + tax + fee == amount
        // (amount is the GROSS draw from the original payment).
        PaymentAllocationService::validateSplit(100.00, 87.00, 0.00, 13.00);
        $this->addToAssertionCount(1);
    }

    public function test_validate_split_rejects_a_split_that_omits_the_fee(): void
    {
        // base + tax alone (87.00) no longer equals amount (100.00) once a
        // fee is part of the picture — the old two-term invariant must not
        // silently pass here.
        $this->expectException(\InvalidArgumentException::class);

        PaymentAllocationService::validateSplit(100.00, 87.00, 0.00, 20.00);
    }

    public function test_validate_split_fee_defaults_to_zero_preserving_the_phase_3b_invariant(): void
    {
        // No 4th argument — Standard/Sales Tax Only call sites are
        // unaffected by the Phase 3C signature widening.
        PaymentAllocationService::validateSplit(50.00, 45.00, 5.00);
        $this->addToAssertionCount(1);
    }

    // ── RefundOperationStatus::isRetryable() ──────────────────────────

    public function test_partially_completed_and_failed_are_retryable_completed_and_pending_are_not(): void
    {
        $this->assertTrue(RefundOperationStatus::PartiallyCompleted->isRetryable());
        $this->assertTrue(RefundOperationStatus::Failed->isRetryable());
        $this->assertFalse(RefundOperationStatus::Completed->isRetryable());
        $this->assertFalse(RefundOperationStatus::Pending->isRetryable());
    }

    // ── proportionalTaxRefund() ────────────────────────────────────────

    private function makeOrder(float $subtotal, float $taxAmount): Order
    {
        $order = new Order();
        $order->subtotal = $subtotal;
        $order->tax_amount = $taxAmount;

        return $order;
    }

    /**
     * A pre-resolved basis, so these stay pure-formula unit tests with no
     * database.
     *
     * proportionalTaxRefund() now sources its rate from
     * HistoricalTaxBasisResolver, which reads persisted order_products rows
     * — an unsaved Order has none. Passing the basis explicitly keeps these
     * tests exercising the extraction arithmetic (which is what they are
     * for) while the resolver's own reconstruction and rejection behavior is
     * covered by HistoricalTaxBasisResolverTest against real rows.
     *
     * These fixtures use a FULLY taxable order, so basis == subtotal and the
     * expected results are identical to the pre-correction values — which is
     * the point: the denominator fix must not move fully-taxable outcomes.
     */
    private function makeBasis(float $taxableBasis, float $taxAmount): HistoricalTaxBasis
    {
        return HistoricalTaxBasis::resolved(
            ordinaryBasisCents:   (int) round($taxableBasis * 100),
            ordinaryTaxCents:     (int) round($taxAmount * 100),
            specialBasisCents:    0,
            specialTaxCents:      0,
            untaxedMerchandiseBasisCents: 0,
            addedFeesCents:       0,
            discountCents:        0,
            lines:                [],
        );
    }

    public function test_proportional_tax_refund_extracts_tax_at_the_original_rate(): void
    {
        // $1000 subtotal + $97.50 tax => 9.75% rate. A $219.50 refund at
        // that rate extracts $19.50 tax (matches the Phase 2/3A formula).
        $order = $this->makeOrder(1000.0, 97.50);

        $tax = PaymentAllocationService::proportionalTaxRefund($order, 219.50, $this->makeBasis(1000.0, 97.50));

        $this->assertEqualsWithDelta(19.50, $tax, 0.01);
    }

    public function test_proportional_tax_refund_is_zero_when_no_tax_was_charged(): void
    {
        $order = $this->makeOrder(1000.0, 0.0);

        $this->assertSame(0.0, PaymentAllocationService::proportionalTaxRefund($order, 500.0, $this->makeBasis(1000.0, 0.0)));
    }

    // ── calculateAllocationSplits(): Standard (no DB — no card-fee lookup) ──

    public function test_calculate_allocation_splits_standard_sums_match_the_combined_total(): void
    {
        $order = $this->makeOrder(1000.0, 97.50);

        $result = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [
                ['original_order_payment_id' => 1, 'amount' => 600.0],
                ['original_order_payment_id' => 2, 'amount' => 400.0],
            ],
            RefundCalculationType::Standard,
            0.0,
            $this->makeBasis(1000.0, 97.50),
        );

        $this->assertCount(2, $result['rows']);
        $this->assertEqualsWithDelta(1000.0, $result['total_amount'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['total_fee'], 0.001);

        // Every row's own base + tax must equal its own amount exactly.
        foreach ($result['rows'] as $row) {
            $this->assertEqualsWithDelta($row['amount'], $row['base'] + $row['tax'], 0.001);
        }

        // The aggregate tax must match a single-shot proportional
        // computation on the combined total — the whole point of
        // remainder-to-last-row rounding.
        $expectedTotalTax = PaymentAllocationService::proportionalTaxRefund($order, 1000.0, $this->makeBasis(1000.0, 97.50));
        $this->assertEqualsWithDelta($expectedTotalTax, $result['total_tax'], 0.001);
    }

    public function test_calculate_allocation_splits_standard_remainder_goes_to_the_last_row(): void
    {
        // Three uneven shares chosen so naive per-row rounding would drift
        // by a cent — the deterministic remainder-to-last-row rule must
        // still land the aggregate exactly on the single-shot total.
        $order = $this->makeOrder(300.0, 33.33);

        $result = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [
                ['original_order_payment_id' => 1, 'amount' => 100.01],
                ['original_order_payment_id' => 2, 'amount' => 100.01],
                ['original_order_payment_id' => 3, 'amount' => 133.31],
            ],
            RefundCalculationType::Standard,
            0.0,
            $this->makeBasis(300.0, 33.33),
        );

        $expectedTotalTax = PaymentAllocationService::proportionalTaxRefund($order, 333.33, $this->makeBasis(300.0, 33.33));
        $this->assertEqualsWithDelta($expectedTotalTax, $result['total_tax'], 0.001);

        foreach ($result['rows'] as $row) {
            $this->assertEqualsWithDelta($row['amount'], $row['base'] + $row['tax'], 0.001);
        }
    }

    // ── calculateAllocationSplits(): Sales Tax Only (no DB) ────────────

    public function test_calculate_allocation_splits_sales_tax_only_has_zero_base_on_every_row(): void
    {
        $order = $this->makeOrder(1000.0, 97.50);

        $result = PaymentAllocationService::calculateAllocationSplits(
            $order,
            new Collection(),
            [
                ['original_order_payment_id' => 1, 'amount' => 60.0],
                ['original_order_payment_id' => 2, 'amount' => 37.50],
            ],
            RefundCalculationType::SalesTaxOnly,
        );

        foreach ($result['rows'] as $row) {
            $this->assertSame(0.0, $row['base']);
            $this->assertSame($row['amount'], $row['tax']);
        }

        $this->assertEqualsWithDelta(97.50, $result['total_tax'], 0.001);
        $this->assertEqualsWithDelta(0.0, $result['total_base'], 0.001);
    }

    // ── Phase 3C refinements: Superseded status + manual-review detection ──

    public function test_superseded_does_not_reserve_balance(): void
    {
        $this->assertFalse(OrderPaymentRefundAllocationStatus::Superseded->reservesBalance());
    }

    public function test_allocation_needs_manual_review_detects_the_marker_text(): void
    {
        $allocation = new \App\Models\Orders\OrderPaymentRefundAllocation();
        $allocation->status = OrderPaymentRefundAllocationStatus::Failed;
        $allocation->failure_reason = 'Refund succeeded at the gateway (ref GW-1) but could not be recorded — do not retry this source; contact support for manual reconciliation.';

        $this->assertTrue(PaymentAllocationService::allocationNeedsManualReview($allocation));
    }

    public function test_allocation_needs_manual_review_is_false_for_an_ordinary_decline(): void
    {
        $allocation = new \App\Models\Orders\OrderPaymentRefundAllocation();
        $allocation->status = OrderPaymentRefundAllocationStatus::Failed;
        $allocation->failure_reason = 'Gateway declined the refund.';

        $this->assertFalse(PaymentAllocationService::allocationNeedsManualReview($allocation));
    }

    public function test_allocation_needs_manual_review_is_false_once_status_moves_off_failed(): void
    {
        // Even if the marker text somehow lingered, a Superseded or
        // Allocated row is no longer an ongoing manual-review case.
        $allocation = new \App\Models\Orders\OrderPaymentRefundAllocation();
        $allocation->status = OrderPaymentRefundAllocationStatus::Superseded;
        $allocation->failure_reason = 'Refund succeeded at the gateway (ref GW-1) but could not be recorded';

        $this->assertFalse(PaymentAllocationService::allocationNeedsManualReview($allocation));
    }
}
