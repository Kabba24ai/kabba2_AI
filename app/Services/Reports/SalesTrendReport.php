<?php

namespace App\Services\Reports;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Sales Trend — Report 2.
 *
 * Answers: "How is revenue / transaction volume / avg ticket trending over time?"
 * Supports grouping by Day, Week, Month, or Year.
 * Returns current period data paired with an equal-length preceding period for comparison.
 */
class SalesTrendReport
{
    public function __construct(private SalesReportingService $reporting) {}

    /**
     * Return bucketed trend data for the given filters.
     *
     * Each bucket contains revenue, transaction count, and avg ticket for
     * both the current period and the immediately preceding equal-length period.
     */
    public function trendData(array $filters): array
    {
        $groupBy = $filters['group_by'] ?? 'day';
        [$start, $end] = $this->reporting->resolveDateRange($filters);

        if (!$start || !$end) {
            return $this->emptyResult($groupBy);
        }

        $currentBuckets = $this->buildBuckets($start->copy(), $end->copy(), $groupBy);
        $bucketCount    = count($currentBuckets);

        if ($bucketCount === 0) {
            return $this->emptyResult($groupBy);
        }

        $currentData = $this->queryGroupedData($filters, $groupBy);

        // Previous period: same bucket count, immediately before the first bucket start.
        // We align to bucket boundaries so week/month/year groupings stay clean.
        $firstBucketStart = $this->alignToBucketStart($start, $groupBy);
        $prevEnd          = $firstBucketStart->copy()->subDay()->endOfDay();
        $prevStart        = $this->calcPrevStart($firstBucketStart, $groupBy, $bucketCount);

        $prevFilters = array_merge($filters, [
            'date_range' => 'custom',
            'start_date' => $prevStart->toDateString(),
            'end_date'   => $prevEnd->toDateString(),
        ]);

        $prevBuckets = $this->buildBuckets($prevStart->copy(), $prevEnd->copy(), $groupBy);
        $prevData    = $this->queryGroupedData($prevFilters, $groupBy);

        $labels = $revenue = $transactions = $avgTicket = [];
        $prevRevenue = $prevTransactions = $prevAvgTicket = [];

        foreach ($currentBuckets as $i => $bucket) {
            $labels[] = $bucket['label'];

            $curr = $currentData->get($bucket['key']);
            $rev  = (float) ($curr->revenue      ?? 0);
            $tx   = (int)   ($curr->transactions ?? 0);
            $revenue[]      = round($rev, 2);
            $transactions[] = $tx;
            $avgTicket[]    = $tx > 0 ? round($rev / $tx, 2) : 0;

            $prevBucket = $prevBuckets[$i] ?? null;
            $prev       = $prevBucket ? $prevData->get($prevBucket['key']) : null;
            $pRev = (float) ($prev->revenue      ?? 0);
            $pTx  = (int)   ($prev->transactions ?? 0);
            $prevRevenue[]      = round($pRev, 2);
            $prevTransactions[] = $pTx;
            $prevAvgTicket[]    = $pTx > 0 ? round($pRev / $pTx, 2) : 0;
        }

        $totalRev     = array_sum($revenue);
        $totalTx      = array_sum($transactions);
        $totalAvg     = $totalTx > 0 ? round($totalRev / $totalTx, 2) : 0;
        $prevTotalRev = array_sum($prevRevenue);
        $prevTotalTx  = array_sum($prevTransactions);
        $prevTotalAvg = $prevTotalTx > 0 ? round($prevTotalRev / $prevTotalTx, 2) : 0;

        return [
            'labels'            => $labels,
            'revenue'           => $revenue,
            'transactions'      => $transactions,
            'avg_ticket'        => $avgTicket,
            'prev_revenue'      => $prevRevenue,
            'prev_transactions' => $prevTransactions,
            'prev_avg_ticket'   => $prevAvgTicket,
            'totals' => [
                'revenue'           => $totalRev,
                'transactions'      => $totalTx,
                'avg_ticket'        => $totalAvg,
                'prev_revenue'      => $prevTotalRev,
                'prev_transactions' => $prevTotalTx,
                'prev_avg_ticket'   => $prevTotalAvg,
                'revenue_growth'    => $prevTotalRev > 0
                    ? round((($totalRev - $prevTotalRev) / $prevTotalRev) * 100, 1) : 0,
                'tx_growth'         => $prevTotalTx > 0
                    ? round((($totalTx - $prevTotalTx) / $prevTotalTx) * 100, 1) : 0,
                'avg_ticket_growth' => $prevTotalAvg > 0
                    ? round((($totalAvg - $prevTotalAvg) / $prevTotalAvg) * 100, 1) : 0,
            ],
            'group_by'         => $groupBy,
            'date_range_label' => $this->reporting->dateRangeLabel($filters),
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────────────

    private function queryGroupedData(array $filters, string $groupBy): Collection
    {
        $expr = $this->groupExpression($groupBy);

        return $this->reporting->baseQuery($filters)
            ->selectRaw("{$expr} AS period_key, SUM(order_products.sub_total) AS revenue, COUNT(DISTINCT orders.id) AS transactions")
            ->groupByRaw($expr)
            ->get()
            ->keyBy('period_key');
    }

    /**
     * SQL expression that collapses each order_date into its bucket key.
     * Keys must match what buildBuckets() generates so lookups succeed.
     *
     *  day:   "2026-06-19"   (date string)
     *  week:  "2026-06-15"   (ISO Monday of that week)
     *  month: "2026-06-01"   (first of month)
     *  year:  "2026"         (year string)
     */
    private function groupExpression(string $groupBy): string
    {
        return match ($groupBy) {
            // WEEKDAY() returns 0=Mon … 6=Sun; subtracting gives us the Monday
            'week'  => "DATE(DATE_SUB(orders.order_date, INTERVAL WEEKDAY(orders.order_date) DAY))",
            'month' => "DATE_FORMAT(orders.order_date, '%Y-%m-01')",
            'year'  => "DATE_FORMAT(orders.order_date, '%Y')",
            default => "DATE(orders.order_date)",
        };
    }

    private function buildBuckets(Carbon $start, Carbon $end, string $groupBy): array
    {
        $buckets = [];

        switch ($groupBy) {
            case 'day':
                $cur = $start->copy()->startOfDay();
                while ($cur->lte($end)) {
                    $buckets[] = ['key' => $cur->toDateString(), 'label' => $cur->format('M j')];
                    $cur->addDay();
                }
                break;

            case 'week':
                $cur = $start->copy()->startOfWeek(Carbon::MONDAY);
                while ($cur->lte($end)) {
                    $buckets[] = [
                        'key'   => $cur->toDateString(),
                        'label' => $cur->format('M j') . '–' . $cur->copy()->addDays(6)->format('M j'),
                    ];
                    $cur->addWeek();
                }
                break;

            case 'month':
                $cur = $start->copy()->startOfMonth();
                while ($cur->lte($end)) {
                    $buckets[] = ['key' => $cur->format('Y-m-01'), 'label' => $cur->format('M Y')];
                    $cur->addMonthNoOverflow();
                }
                break;

            case 'year':
                $cur = $start->copy()->startOfYear();
                while ($cur->lte($end)) {
                    $buckets[] = ['key' => $cur->format('Y'), 'label' => $cur->format('Y')];
                    $cur->addYear();
                }
                break;
        }

        return $buckets;
    }

    private function alignToBucketStart(Carbon $date, string $groupBy): Carbon
    {
        return match ($groupBy) {
            'week'  => $date->copy()->startOfWeek(Carbon::MONDAY),
            'month' => $date->copy()->startOfMonth(),
            'year'  => $date->copy()->startOfYear(),
            default => $date->copy()->startOfDay(),
        };
    }

    private function calcPrevStart(Carbon $alignedStart, string $groupBy, int $bucketCount): Carbon
    {
        return match ($groupBy) {
            'week'  => $alignedStart->copy()->subWeeks($bucketCount),
            'month' => $alignedStart->copy()->subMonths($bucketCount),
            'year'  => $alignedStart->copy()->subYears($bucketCount),
            default => $alignedStart->copy()->subDays($bucketCount),
        };
    }

    private function emptyResult(string $groupBy): array
    {
        return [
            'labels' => [], 'revenue' => [], 'transactions' => [], 'avg_ticket' => [],
            'prev_revenue' => [], 'prev_transactions' => [], 'prev_avg_ticket' => [],
            'totals' => [
                'revenue' => 0, 'transactions' => 0, 'avg_ticket' => 0,
                'prev_revenue' => 0, 'prev_transactions' => 0, 'prev_avg_ticket' => 0,
                'revenue_growth' => 0, 'tx_growth' => 0, 'avg_ticket_growth' => 0,
            ],
            'group_by'         => $groupBy,
            'date_range_label' => '',
        ];
    }
}
