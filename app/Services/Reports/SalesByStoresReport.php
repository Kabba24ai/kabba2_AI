<?php

namespace App\Services\Reports;

use App\Services\Reports\Concerns\NetsRefundedRevenue;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sales By Stores — Report 3.
 *
 * Answers: "How does revenue, volume, and avg ticket compare across locations?"
 * Returns one row per store with current-period metrics and vs-previous-period deltas.
 */
class SalesByStoresReport
{
    use NetsRefundedRevenue;

    public function __construct(
        private SalesReportingService $reporting,
        private BillingRevenueAttributionService $billingAttribution,
    ) {}

    /**
     * Return per-store metrics for the current period, each paired with the
     * immediately preceding equal-length period for growth comparison.
     */
    public function storeData(array $filters): array
    {
        $currentRows = $this->queryByStore($filters);

        // Previous period: same-length window immediately before current start
        [$start, $end] = $this->reporting->resolveDateRange($filters);
        $prevRows = collect();

        if ($start && $end) {
            $days      = (int) $start->diffInDays($end) + 1;
            $prevEnd   = $start->copy()->subDay()->endOfDay();
            $prevStart = $prevEnd->copy()->subDays($days - 1)->startOfDay();

            $prevFilters = array_merge($filters, [
                'date_range' => 'custom',
                'start_date' => $prevStart->toDateString(),
                'end_date'   => $prevEnd->toDateString(),
            ]);

            $prevRows = $this->queryByStore($prevFilters)->keyBy('store_name');
        }

        // Compute totals for % share
        $totalRev = $currentRows->sum('revenue');
        $totalTx  = $currentRows->sum('transactions');

        $stores = $currentRows->map(function ($row) use ($prevRows, $totalRev, $totalTx) {
            $name = $row->store_name;
            $rev  = (float) $row->revenue;
            $tx   = (int)   $row->transactions;
            $avg  = $tx > 0 ? round($rev / $tx, 2) : 0;

            $prev = $prevRows->get($name);
            $pRev = (float) ($prev->revenue      ?? 0);
            $pTx  = (int)   ($prev->transactions ?? 0);
            $pAvg = $pTx > 0 ? round($pRev / $pTx, 2) : 0;

            return [
                'name'              => $name,
                'revenue'           => round($rev, 2),
                'transactions'      => $tx,
                'avg_ticket'        => $avg,
                'prev_revenue'      => round($pRev, 2),
                'prev_transactions' => $pTx,
                'prev_avg_ticket'   => $pAvg,
                'revenue_share'     => $totalRev > 0 ? round(($rev / $totalRev) * 100, 1) : 0,
                'tx_share'          => $totalTx  > 0 ? round(($tx  / $totalTx)  * 100, 1) : 0,
                'revenue_growth'    => $pRev > 0 ? round((($rev - $pRev) / $pRev) * 100, 1) : 0,
                'tx_growth'         => $pTx  > 0 ? round((($tx  - $pTx)  / $pTx)  * 100, 1) : 0,
                'avg_ticket_growth' => $pAvg > 0 ? round((($avg - $pAvg) / $pAvg) * 100, 1) : 0,
            ];
        })->values()->toArray();

        // Overall totals
        $prevTotal     = $prevRows->sum('revenue');
        $prevTotalTx   = $prevRows->sum('transactions');
        $prevTotalAvg  = $prevTotalTx > 0 ? round($prevTotal / $prevTotalTx, 2) : 0;
        $totalAvg      = $totalTx     > 0 ? round($totalRev  / $totalTx,     2) : 0;

        return [
            'stores' => $stores,
            'totals' => [
                'revenue'           => round($totalRev, 2),
                'transactions'      => $totalTx,
                'avg_ticket'        => $totalAvg,
                'prev_revenue'      => round($prevTotal, 2),
                'prev_transactions' => $prevTotalTx,
                'prev_avg_ticket'   => $prevTotalAvg,
                'revenue_growth'    => $prevTotal   > 0 ? round((($totalRev - $prevTotal)   / $prevTotal)   * 100, 1) : 0,
                'tx_growth'         => $prevTotalTx > 0 ? round((($totalTx  - $prevTotalTx) / $prevTotalTx) * 100, 1) : 0,
                'avg_ticket_growth' => $prevTotalAvg > 0 ? round((($totalAvg - $prevTotalAvg) / $prevTotalAvg) * 100, 1) : 0,
            ],
            'date_range_label' => $this->reporting->dateRangeLabel($filters),
        ];
    }

    private function queryByStore(array $filters): Collection
    {
        // Payment Architecture Finalization: this previously summed raw
        // order_products.sub_total with zero refund awareness — a fully
        // refunded order's original line revenue counted in full here (and
        // fed straight into the Dashboard's Sales-By-Store preview card).
        // Now shares the same exclude-fully-refunded + proportional
        // partial-refund netting ProductSalesPerformanceEngine already uses.
        $expr = $this->netRevenueExpr();
        $rows = $this->applyRefundNetting($this->reporting->baseQuery($filters))
            ->selectRaw("
                COALESCE(stores.store_name, 'Unassigned')  AS store_name,
                SUM({$expr})                                AS revenue,
                COUNT(DISTINCT orders.id)                   AS transactions
            ")
            ->groupBy('order_products.delivery_store_id', 'stores.store_name')
            ->orderByDesc('revenue')
            ->get();

        // Attributed Billing Engine revenue (extensions) lands in the parent
        // rental's store bucket — revenue only, transaction counts untouched
        foreach ($this->billingAttribution->groupedRevenue('store', $filters) as $billing) {
            $name     = $billing->store_name ?? 'Unassigned';
            $existing = $rows->first(fn ($r) => $r->store_name === $name);

            if ($existing) {
                $existing->revenue = (float) $existing->revenue + (float) $billing->revenue;
            } else {
                $rows->push((object) [
                    'store_name'   => $name,
                    'revenue'      => (float) $billing->revenue,
                    'transactions' => 0,
                ]);
            }
        }

        return $rows->sortByDesc(fn ($r) => (float) $r->revenue)->values();
    }
}
