<?php

namespace App\Services\Reports\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Payment Architecture Finalization — shared "demand-style" refund netting:
 * exclude fully-refunded orders entirely, and proportionally reduce each
 * line's sub_total by that order's share of partial refunds. Originally
 * written once, inline, inside ProductSalesPerformanceEngine::demandQuery()
 * — extracted here so every report grouping order_products.sub_total by
 * product/category/store applies the identical netting instead of counting
 * a refunded order's original revenue with no netting at all (the bug this
 * fixes in ProductSalesRankingReport and SalesByStoresReport, which had no
 * refund awareness whatsoever before this).
 */
trait NetsRefundedRevenue
{
    use HasAllocationAwareRefundSql;

    /**
     * Exclude orders that have been fully refunded, and join the two
     * subqueries netRevenueExpr() depends on: each order's gross sub_total
     * total (needed as the ratio denominator when a query groups by
     * product/store rather than by order) and its allocation-aware summed
     * partial-refund amount.
     */
    protected function applyRefundNetting(Builder $query): Builder
    {
        $query->whereNotExists(function ($sub) {
            $sub->selectRaw('1')
                ->from('order_payments as rp')
                ->whereColumn('rp.order_id', 'orders.id')
                ->where('rp.status', 'Refunded')
                ->whereNull('rp.deleted_at');
        });

        $query->leftJoinSub(
            DB::table('order_products as op_gross')
                ->selectRaw('order_id, SUM(sub_total) AS order_gross')
                ->whereNull('deleted_at')
                ->groupBy('order_id'),
            'order_totals',
            'order_totals.order_id',
            '=',
            'orders.id'
        );

        $refundAmountSql = $this->allocationAwareRefundAmountSql('op_ref');
        $query->leftJoinSub(
            DB::table('order_payments as op_ref')
                ->selectRaw("order_id, SUM({$refundAmountSql}) AS partial_refunded")
                ->where('status', 'Partial Refund')
                ->whereNull('deleted_at')
                ->groupBy('order_id'),
            'order_refunds',
            'order_refunds.order_id',
            '=',
            'orders.id'
        );

        return $query;
    }

    /**
     * Per-row net revenue expression (no SUM — callers wrap in SUM as
     * needed). ratio = (order_gross - partial_refunded) / order_gross;
     * net = sub_total * GREATEST(0, ratio) — floored at 0, never negative.
     * Falls back to sub_total when order_totals has no join match.
     */
    protected function netRevenueExpr(): string
    {
        return "order_products.sub_total * GREATEST(0,
            (COALESCE(order_totals.order_gross, order_products.sub_total) - COALESCE(order_refunds.partial_refunded, 0))
            / NULLIF(COALESCE(order_totals.order_gross, order_products.sub_total), 0)
        )";
    }
}
