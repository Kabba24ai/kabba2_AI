<?php

namespace App\Services\Dashboard;

use App\Services\Reports\SalesReportEngineV2;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\SalesByStoresReport;
use App\Services\Reports\EmployeePerformanceEngine;
use Carbon\Carbon;

/**
 * Dashboard V2 — Financial Overview presenter.
 *
 * This is the ONLY financial code in the dashboard, and it is deliberately
 * thin: it maps dashboard periods to canonical report filters, calls the
 * canonical Sales Reporting Engine services under app/Services/Reports/, and
 * assembles view-ready arrays. It contains NO SQL and NO revenue arithmetic —
 * every net-sales figure, trend point, and preview row is produced by the
 * engine that the Sales Reports pages (and the React API) already use, so the
 * dashboard reconciles with those reports by construction.
 *
 * The single percentage it derives (growth rate for the Last Month calendar
 * comparison) reuses the engine's own formula verbatim — see growthPct().
 */
class DashboardFinancialPresenter
{
    /** KPI/trend payment scope — matches the Pure Sales Summary default. */
    private const KPI_PAYMENT_STATUS = 'paid';

    /** Preview cards render for month-to-date, matching their destination reports. */
    private const PREVIEW_RANGE = 'mtd';

    public function __construct(
        private SalesReportEngineV2 $engine,
        private ProductSalesPerformanceEngine $products,
        private SalesByStoresReport $stores,
        private EmployeePerformanceEngine $employees,
    ) {}

    // ─── KPI + Trend ────────────────────────────────────────────────────────

    /**
     * Three dashboard periods, each shaped for the existing chart JS
     * (categories/current/previous/totalSales/previousTotalSales) plus the
     * canonical growthRate and dailyAverage so the JS never does math.
     */
    public function salesData(): array
    {
        return [
            'rolling30' => $this->period('last_30'),
            'mtd'       => $this->period('mtd'),
            'lastMonth' => $this->period('last_month'),
        ];
    }

    private function period(string $range): array
    {
        $filters = ['date_range' => $range, 'payment_status' => self::KPI_PAYMENT_STATUS];
        $trend   = $this->engine->trendData($filters);

        if ($range === 'last_month') {
            // Approved semantics: Last Month compares to the preceding CALENDAR
            // month, not the engine's default equal-length window. The engine's
            // periodComparison() computes netSales/previousNetSales/growthRate
            // for that explicit window — the dashboard performs no financial math.
            [$prevStart, $prevEnd] = $this->priorCalendarMonthWindow();
            $prevFilters = [
                'date_range'     => 'custom',
                'start_date'     => $prevStart,
                'end_date'       => $prevEnd,
                'payment_status' => self::KPI_PAYMENT_STATUS,
            ];

            $comparison = $this->engine->periodComparison(
                ['date_range' => 'last_month', 'payment_status' => self::KPI_PAYMENT_STATUS],
                $prevStart,
                $prevEnd
            );

            // Chart comparison line: the prior calendar month's canonical daily
            // series, aligned index-for-index to the current month's day count.
            $prevTrend          = $this->engine->trendData($prevFilters);
            $previousSeries     = $this->alignSeries($prevTrend['current'], count($trend['current']));
            $previousTotalSales = $comparison['previousNetSales'];
            $growthRate         = $comparison['growthRate'];
        } else {
            $previousSeries    = $trend['previous'];
            $previousTotalSales = $trend['previousNetSales'];
            $growthRate        = $trend['growthRate'];
        }

        return [
            'categories'         => $trend['categories'],
            'current'            => $trend['current'],
            'previous'           => $previousSeries,
            'totalSales'         => $trend['netSales'],
            'previousTotalSales' => $previousTotalSales,
            'growthRate'         => $growthRate,
            'dailyAverage'       => $trend['dailyNetAverage'],
        ];
    }

    /** Prior calendar month relative to "last month" (i.e. two months before now). */
    private function priorCalendarMonthWindow(): array
    {
        $anchor = Carbon::now()->subMonthsNoOverflow(2);
        return [
            $anchor->copy()->startOfMonth()->toDateString(),
            $anchor->copy()->endOfMonth()->toDateString(),
        ];
    }

    /**
     * Pad/trim a canonical daily series to the reference length so the chart's
     * two lines align index-for-index. Presentation alignment only — the values
     * are unchanged and the "Previous Period" KPI still reflects the full month.
     */
    private function alignSeries(array $series, int $length): array
    {
        $out = array_slice($series, 0, $length);
        return array_pad($out, $length, 0.0);
    }

    // ─── Executive preview cards (top N, month-to-date) ──────────────────────

    public function topProducts(int $limit = 5): array
    {
        $filters = [
            'date_range'     => self::PREVIEW_RANGE,
            'payment_status' => 'paid_and_account', // Product Performance canonical default
            'sale_type'      => 'all',
            'sort_by'        => 'revenue',
            'limit'          => (string) $limit,
        ];
        $data = $this->products->productData($filters);

        return array_map(fn ($row) => [
            'name'    => $row['product_name'],
            'revenue' => $row['revenue'],
        ], $data['rows']);
    }

    public function topCategories(int $limit = 5): array
    {
        $filters = [
            'date_range'     => self::PREVIEW_RANGE,
            'payment_status' => 'paid_and_account',
            'sale_type'      => 'all',
            'sort_by'        => 'revenue',
            'limit'          => (string) $limit,
        ];
        $data = $this->products->categoryData($filters);

        return array_map(fn ($row) => [
            'name'    => $row['category_name'],
            'revenue' => $row['revenue'],
        ], $data['rows']);
    }

    public function salesByStore(int $limit = 5): array
    {
        $filters = [
            'date_range'     => self::PREVIEW_RANGE,
            'payment_status' => 'paid', // Sales By Stores canonical default
        ];
        $data = $this->stores->storeData($filters);

        return array_map(fn ($store) => [
            'name'    => $store['name'],
            'revenue' => $store['revenue'],
        ], array_slice($data['stores'], 0, $limit));
    }

    public function employeePerformance(int $limit = 5): array
    {
        // Engine handles its own payment scope internally (qualified revenue).
        $filters = ['date_range' => self::PREVIEW_RANGE, 'sale_type' => 'all'];
        $data    = $this->employees->reportData($filters, [], 'single');

        return array_map(fn ($emp) => [
            'name'    => $emp['name'],
            'revenue' => $emp['qualified_revenue'],
        ], array_slice($data['employees'], 0, $limit));
    }

    // ─── Report destinations (existing routes only) ──────────────────────────

    public function reportRoutes(): array
    {
        return [
            'view_full'      => route('admin.reports.sales-reports.pure-sales-summary.index'),
            'sales_trend'    => route('admin.reports.sales-reports.sales-trend.index'),
            'pure_sales'     => route('admin.reports.sales-reports.pure-sales-summary.index'),
            'top_products'   => route('admin.reports.sales-reports.product-performance.index', ['view' => 'products']),
            'top_categories' => route('admin.reports.sales-reports.product-performance.index', ['view' => 'categories']),
            'sales_by_store' => route('admin.reports.sales-reports.sales-by-stores.index'),
            'employee'       => route('admin.reports.sales-reports.employee-performance.index'),
            'sales_tax'      => route('admin.reports.sales-tax.index'),
        ];
    }
}
