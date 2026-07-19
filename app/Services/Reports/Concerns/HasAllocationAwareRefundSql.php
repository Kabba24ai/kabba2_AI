<?php

namespace App\Services\Reports\Concerns;

/**
 * Payment Architecture Finalization — shared allocation-aware refund SQL
 * fragments. Originally written once, inline, inside SalesReportEngineV2
 * (Phase 3D) to fix SUM(op.refund_amount) overstating refunds whenever a
 * Phase 3C multi-source refund partially or fully failed — a refund row's
 * stated refund_amount can be ahead of what was actually returned; only
 * Allocated allocation rows are real money. Extracted here so every report
 * engine that nets refunds off order_payments uses the identical formula
 * (the PHP equivalent lives in PaymentAllocationService::totalSuccessfulRefunded())
 * instead of re-deriving or, worse, quietly summing the raw column.
 */
trait HasAllocationAwareRefundSql
{
    /**
     * Net (fee-excluded) refunded amount for ONE order_payments row aliased
     * $alias — sums Allocated allocations when the row has any, falls back
     * to the row's own refund_amount for legacy rows with none yet.
     */
    protected function allocationAwareRefundAmountSql(string $alias = 'op'): string
    {
        return "CASE WHEN EXISTS (
                    SELECT 1 FROM order_payment_refund_allocations opra_exists
                    WHERE opra_exists.refund_order_payment_id = {$alias}.id
                )
                THEN COALESCE((
                    SELECT SUM(opra_sum.allocated_amount - COALESCE(opra_sum.processing_fee_retained, 0))
                    FROM order_payment_refund_allocations opra_sum
                    WHERE opra_sum.refund_order_payment_id = {$alias}.id AND opra_sum.status = 'allocated'
                ), 0)
                ELSE {$alias}.refund_amount
                END";
    }

    /**
     * Allocation-aware counterpart to allocationAwareRefundAmountSql() for
     * the tax portion — same has-allocations branch, summing
     * allocated_tax_amount instead.
     */
    protected function allocationAwareRefundTaxSql(string $alias = 'op'): string
    {
        return "CASE WHEN EXISTS (
                    SELECT 1 FROM order_payment_refund_allocations opra_tax_exists
                    WHERE opra_tax_exists.refund_order_payment_id = {$alias}.id
                )
                THEN COALESCE((
                    SELECT SUM(opra_tax_sum.allocated_tax_amount)
                    FROM order_payment_refund_allocations opra_tax_sum
                    WHERE opra_tax_sum.refund_order_payment_id = {$alias}.id AND opra_tax_sum.status = 'allocated'
                ), 0)
                ELSE COALESCE({$alias}.tax_refunded, 0)
                END";
    }
}
