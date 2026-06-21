<?php

namespace App\Services\Reports;

use Carbon\Carbon;

/**
 * SalesTrendAnalysisEngine — orchestrates monthly/yearly Net Sales trend data.
 *
 * This class contains ZERO accounting formulas. All revenue calculation is
 * delegated to SalesReportEngineV2::netSalesForPeriod(), which calls the
 * verified snapshot() path. This guarantees that any year/month value here
 * will reconcile exactly with Pure Sales Summary for the same date range.
 *
 * Maximum 3 total years (1 primary + 2 comparison) is enforced in reportData().
 */
class SalesTrendAnalysisEngine
{
    private const MONTHS = [
        1 => 'Jan', 2 => 'Feb',  3 => 'Mar',  4 => 'Apr',
        5 => 'May', 6 => 'Jun',  7 => 'Jul',  8 => 'Aug',
        9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec',
    ];

    public function __construct(private SalesReportEngineV2 $engine) {}

    /**
     * Build the full report payload for the Blade view and AJAX responses.
     *
     * @param  int    $primaryYear   The main year displayed in the primary chart.
     * @param  int[]  $compareYears  Up to 2 additional years for the YOY chart (enforced here).
     * @param  array  $filters       store, sale_type, category, product, payment_status.
     */
    public function reportData(int $primaryYear, array $compareYears, array $filters): array
    {
        // ── Enforce 3-year maximum ────────────────────────────────────────────
        $compareYears = $this->sanitizeCompareYears($compareYears, $primaryYear);

        // ── Fetch monthly net sales for all requested years ───────────────────
        $yearSeries = [];
        foreach (array_merge([$primaryYear], $compareYears) as $year) {
            $yearSeries[$year] = $this->monthlyNetSales($year, $filters);
        }

        // Always fetch primaryYear - 1 for KPI "Prior Year Same Period" card,
        // even if the user did not select it as a comparison year.
        $priorYear = $primaryYear - 1;
        if (!isset($yearSeries[$priorYear])) {
            $yearSeries[$priorYear] = $this->monthlyNetSales($priorYear, $filters);
        }

        // ── Build output ──────────────────────────────────────────────────────
        $series = [];
        foreach (array_merge([$primaryYear], $compareYears) as $year) {
            $series[] = [
                'year' => $year,
                'data' => array_values($yearSeries[$year]),   // 0-indexed, Jan=0, Dec=11
            ];
        }

        return [
            'months'        => array_values(self::MONTHS),
            'series'        => $series,
            'primary_year'  => $primaryYear,
            'compare_years' => $compareYears,
            'kpis'          => $this->buildKpis($primaryYear, $priorYear, $yearSeries, $compareYears),
        ];
    }

    /**
     * Return monthly net sales for a given year, indexed 1 (Jan) through 12 (Dec).
     *
     * Future months always return 0.0 and are never omitted — the chart always
     * renders all 12 months even when some have no data yet.
     *
     * Current-month end date is capped at today so we don't project forward.
     */
    public function monthlyNetSales(int $year, array $filters): array
    {
        $result = [];
        $today  = Carbon::today();

        for ($month = 1; $month <= 12; $month++) {
            $start = Carbon::create($year, $month, 1)->startOfDay();
            $end   = $start->copy()->endOfMonth()->endOfDay();

            if ($start->isAfter($today)) {
                $result[$month] = 0.0;
                continue;
            }

            if ($end->isAfter($today)) {
                $end = $today->copy()->endOfDay();
            }

            $result[$month] = $this->engine->netSalesForPeriod(
                $start->toDateString(),
                $end->toDateString(),
                $filters
            );
        }

        return $result;
    }

    // ─── Private helpers ──────────────────────────────────────────────────────

    /**
     * Clamp compareYears: max 2, no duplicates, no primary year, valid integers.
     *
     * @return int[]
     */
    private function sanitizeCompareYears(array $raw, int $primaryYear): array
    {
        return array_values(array_unique(
            array_slice(
                array_filter(
                    array_map('intval', $raw),
                    fn($y) => $y > 2000 && $y !== $primaryYear
                ),
                0, 2
            )
        ));
    }

    /**
     * Build KPI card values for the primary year.
     *
     * "YTD" means all completed months if viewing a past year, or months through
     * today's month if viewing the current year.
     */
    private function buildKpis(int $primaryYear, int $priorYear, array $yearSeries, array $compareYears): array
    {
        $today        = Carbon::today();
        $primaryData  = $yearSeries[$primaryYear];
        $priorData    = $yearSeries[$priorYear] ?? [];

        $currentMonth = ($primaryYear === (int)$today->year)
            ? (int)$today->month
            : 12;

        $ytdNetSales = 0.0;
        $priorYtd    = 0.0;
        $bestNet     = null;
        $worstNet    = null;
        $bestMonth   = '—';
        $worstMonth  = '—';
        $monthCount  = 0;

        for ($m = 1; $m <= $currentMonth; $m++) {
            $val      = (float) ($primaryData[$m] ?? 0);
            $priorVal = (float) ($priorData[$m]   ?? 0);
            $ytdNetSales += $val;
            $priorYtd    += $priorVal;
            $monthCount++;

            if ($bestNet === null || $val >= $bestNet) {
                $bestNet   = $val;
                $bestMonth = self::MONTHS[$m];
            }
            if ($worstNet === null || $val <= $worstNet) {
                $worstNet   = $val;
                $worstMonth = self::MONTHS[$m];
            }
        }

        $dollarChange = $ytdNetSales - $priorYtd;
        $pctChange    = $priorYtd != 0
            ? round(($dollarChange / abs($priorYtd)) * 100, 1)
            : 0.0;
        $avgMonthly   = $monthCount > 0 ? round($ytdNetSales / $monthCount, 2) : 0.0;

        // ── YOY summary (secondary chart KPIs) ────────────────────────────────
        $yoyTotals = [];
        foreach (array_merge([$primaryYear], $compareYears) as $year) {
            $yoyTotals[] = [
                'year'  => $year,
                'total' => round(array_sum($yearSeries[$year] ?? []), 2),
            ];
        }
        usort($yoyTotals, fn($a, $b) => $b['total'] <=> $a['total']);

        // Best month across all selected years (sum across years per month)
        $bestMonthOverall    = '—';
        $bestMonthSumOverall = null;
        for ($m = 1; $m <= 12; $m++) {
            $sum = 0.0;
            foreach (array_merge([$primaryYear], $compareYears) as $y) {
                $sum += (float) ($yearSeries[$y][$m] ?? 0);
            }
            if ($bestMonthSumOverall === null || $sum > $bestMonthSumOverall) {
                $bestMonthSumOverall = $sum;
                $bestMonthOverall    = self::MONTHS[$m];
            }
        }

        return [
            // Primary-year KPI cards
            'ytd_net_sales'          => round($ytdNetSales, 2),
            'prior_year'             => $priorYear,
            'prior_year_same_period' => round($priorYtd, 2),
            'dollar_change'          => round($dollarChange, 2),
            'pct_change'             => $pctChange,
            'best_month'             => $bestMonth,
            'worst_month'            => $worstMonth,
            'avg_monthly_net_sales'  => $avgMonthly,
            'months_counted'         => $monthCount,
            // YOY section
            'yoy_totals'             => $yoyTotals,
            'best_year'              => ($yoyTotals[0]['year'] ?? $primaryYear),
            'best_month_overall'     => $bestMonthOverall,
        ];
    }
}
