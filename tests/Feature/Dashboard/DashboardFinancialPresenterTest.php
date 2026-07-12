<?php

namespace Tests\Feature\Dashboard;

use App\Http\Controllers\Admin\Dashboard\IndexController;
use App\Services\Dashboard\DashboardFinancialPresenter;
use App\Services\Reports\EmployeePerformanceEngine;
use App\Services\Reports\ProductSalesPerformanceEngine;
use App\Services\Reports\SalesByStoresReport;
use App\Services\Reports\SalesReportEngineV2;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Dashboard V2 — Phase 3 Financial Overview.
 *
 * Proves the dashboard is a pure presentation layer: every financial value it
 * shows is produced by the canonical Sales Reporting Engine (the same services
 * the Sales Reports pages use), and the legacy dashboard-specific calculation
 * methods no longer exist. Comparisons are against direct canonical calls, so
 * they hold on any dataset (including an empty test DB).
 */
class DashboardFinancialPresenterTest extends TestCase
{
    use RefreshDatabase;

    private function presenter(): DashboardFinancialPresenter
    {
        return app(DashboardFinancialPresenter::class);
    }

    /** @test */
    public function total_sales_reconciles_with_canonical_engine_net_sales(): void
    {
        $engine = app(SalesReportEngineV2::class);
        $sales  = $this->presenter()->salesData();

        foreach (['rolling30' => 'last_30', 'mtd' => 'mtd', 'lastMonth' => 'last_month'] as $key => $range) {
            $canonical = $engine->kpis(['date_range' => $range, 'payment_status' => 'paid'])['net_sales'];

            $this->assertEqualsWithDelta(
                (float) $canonical,
                (float) $sales[$key]['totalSales'],
                0.01,
                "Total Sales for {$key} must equal canonical net_sales"
            );

            // Engine guarantee: the daily series sums to the reported total.
            $this->assertEqualsWithDelta(
                (float) $sales[$key]['totalSales'],
                array_sum($sales[$key]['current']),
                0.01,
                "sum(current) must equal totalSales for {$key}"
            );
        }
    }

    /** @test */
    public function growth_daily_average_and_previous_delegate_to_trend_for_rolling_and_mtd(): void
    {
        $engine = app(SalesReportEngineV2::class);
        $sales  = $this->presenter()->salesData();

        foreach (['rolling30' => 'last_30', 'mtd' => 'mtd'] as $key => $range) {
            $trend = $engine->trendData(['date_range' => $range, 'payment_status' => 'paid']);

            $this->assertEqualsWithDelta($trend['growthRate'], $sales[$key]['growthRate'], 0.01);
            $this->assertEqualsWithDelta($trend['dailyNetAverage'], $sales[$key]['dailyAverage'], 0.01);
            $this->assertEqualsWithDelta($trend['previousNetSales'], $sales[$key]['previousTotalSales'], 0.01);
        }
    }

    /** @test */
    public function last_month_totals_and_growth_come_from_canonical_period_comparison(): void
    {
        $engine = app(SalesReportEngineV2::class);
        $anchor = Carbon::now()->subMonthsNoOverflow(2);
        $prevStart = $anchor->copy()->startOfMonth()->toDateString();
        $prevEnd   = $anchor->copy()->endOfMonth()->toDateString();

        // The single canonical source for Last Month's three figures.
        $comparison = $engine->periodComparison(
            ['date_range' => 'last_month', 'payment_status' => 'paid'],
            $prevStart,
            $prevEnd
        );

        // Total Sales must be the full last calendar month (date_range=last_month).
        $fullLastMonthNet = $engine->kpis(['date_range' => 'last_month', 'payment_status' => 'paid'])['net_sales'];
        $this->assertEqualsWithDelta((float) $fullLastMonthNet, (float) $comparison['netSales'], 0.01);

        // Previous Period must be the full preceding calendar month.
        $priorCalendarMonthNet = $engine->netSalesForPeriod($prevStart, $prevEnd, ['payment_status' => 'paid']);
        $this->assertEqualsWithDelta((float) $priorCalendarMonthNet, (float) $comparison['previousNetSales'], 0.01);

        $sales = $this->presenter()->salesData();

        $this->assertEqualsWithDelta((float) $comparison['netSales'], (float) $sales['lastMonth']['totalSales'], 0.01,
            'Last Month Total Sales must equal the canonical full last calendar month net sales');
        $this->assertEqualsWithDelta((float) $comparison['previousNetSales'], (float) $sales['lastMonth']['previousTotalSales'], 0.01,
            'Last Month Previous Period must equal the canonical prior calendar month net sales');
        $this->assertSame($comparison['growthRate'], $sales['lastMonth']['growthRate'],
            'Last Month Growth Rate must come directly from the canonical engine periodComparison()');
    }

    /** @test */
    public function presenter_performs_no_growth_rate_arithmetic(): void
    {
        // The dashboard presenter must own no growth/revenue formula of its own.
        foreach (['growthPct', 'growthRate', 'calculateGrowth'] as $method) {
            $this->assertFalse(
                method_exists(DashboardFinancialPresenter::class, $method),
                "Presenter must not define {$method}() — growth is canonical engine logic"
            );
        }
    }

    /** @test */
    public function preview_cards_delegate_to_the_canonical_engines(): void
    {
        $products = app(ProductSalesPerformanceEngine::class);
        $stores   = app(SalesByStoresReport::class);
        $emps     = app(EmployeePerformanceEngine::class);
        $p        = $this->presenter();

        $expectedProducts = collect($products->productData([
            'date_range' => 'mtd', 'payment_status' => 'paid_and_account',
            'sale_type' => 'all', 'sort_by' => 'revenue', 'limit' => '5',
        ])['rows'])->map(fn ($r) => ['name' => $r['product_name'], 'revenue' => $r['revenue']])->all();
        $this->assertSame($expectedProducts, $p->topProducts());

        $expectedCategories = collect($products->categoryData([
            'date_range' => 'mtd', 'payment_status' => 'paid_and_account',
            'sale_type' => 'all', 'sort_by' => 'revenue', 'limit' => '5',
        ])['rows'])->map(fn ($r) => ['name' => $r['category_name'], 'revenue' => $r['revenue']])->all();
        $this->assertSame($expectedCategories, $p->topCategories());

        $expectedStores = collect($stores->storeData(['date_range' => 'mtd', 'payment_status' => 'paid'])['stores'])
            ->take(5)->map(fn ($s) => ['name' => $s['name'], 'revenue' => $s['revenue']])->values()->all();
        $this->assertSame($expectedStores, $p->salesByStore());

        $expectedEmployees = collect($emps->reportData(['date_range' => 'mtd', 'sale_type' => 'all'], [], 'single')['employees'])
            ->take(5)->map(fn ($e) => ['name' => $e['name'], 'revenue' => $e['qualified_revenue']])->values()->all();
        $this->assertSame($expectedEmployees, $p->employeePerformance());
    }

    /** @test */
    public function legacy_dashboard_financial_methods_are_removed(): void
    {
        foreach ([
            'getRevenueRows', 'getSalesData', 'getRolling30DaysData',
            'getCurrentMonthData', 'getLastMonthData', 'getMaintenanceChartData',
        ] as $method) {
            $this->assertFalse(
                method_exists(IndexController::class, $method),
                "Legacy method {$method}() must no longer exist on the dashboard controller"
            );
        }
    }

    /** @test */
    public function report_shortcut_routes_all_resolve_to_existing_reports(): void
    {
        foreach ($this->presenter()->reportRoutes() as $name => $url) {
            $this->assertIsString($url, "Route {$name} must resolve to a URL");
            $this->assertStringContainsString('/reports/', $url, "Route {$name} must target the reports area");
        }
    }
}
