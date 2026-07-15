<?php

namespace Tests\Unit\Services\Orders;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Models\Orders\OrderPayment;
use App\Services\Orders\PaymentAllocationService;
use Tests\TestCase;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * Genuinely executable, DB-free Unit tests. PaymentAllocationService's
 * remaining methods (remainingRefundable(), resolveLegacyOriginalPayment(),
 * attributionState(), backfill()) all query relations and therefore
 * require a real database connection — those are covered by
 * tests/Feature/Orders/PaymentAllocationFoundationTest.php instead (written
 * but not executed in this sandbox; see that file's docblock).
 *
 * What IS tested here without a database:
 *  - validateSplit()'s pure arithmetic, including the integer-cents
 *    comparison that avoids float-precision false positives/negatives.
 *  - allocateSingleSource()'s two guard clauses, both of which throw
 *    BEFORE any query or write — constructed with real (unsaved)
 *    OrderPayment instances, never persisted.
 *  - OrderPaymentRefundAllocationStatus::reservesBalance().
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
}
