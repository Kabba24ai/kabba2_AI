{{--
    Dashboard V2 — Phase 3 Financial Overview.

    Pure presentation layer over the canonical Sales Reporting Engine. Every
    figure here is assembled by App\Services\Dashboard\DashboardFinancialPresenter
    (KPIs/trend from SalesReportEngineV2; previews from ProductSalesPerformanceEngine,
    SalesByStoresReport, EmployeePerformanceEngine). No financial math lives in this
    view or in the JS — the KPI/trend numbers come pre-computed in $salesData and the
    chart JS only renders them.
--}}

{{-- Section header --}}
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div class="flex items-center gap-2">
        <div class="w-9 h-9 rounded-lg bg-indigo-100 flex items-center justify-center">
            <x-heroicon-o-chart-bar class="w-5 h-5 text-indigo-600" />
        </div>
        <div>
            <h2 class="text-lg font-semibold text-gray-900">Financial Overview</h2>
            <p class="text-sm text-gray-500">Key performance metrics from the Sales Reporting Engine</p>
        </div>
    </div>
    <a href="{{ $reportRoutes['view_full'] }}"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-700">
        View Full Reports
        <x-heroicon-o-arrow-top-right-on-square class="w-4 h-4" />
    </a>
</div>

{{-- KPI cards --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-blue-50 border border-blue-200 rounded-xl p-4 text-center flex flex-col justify-center min-h-[92px]">
        <div class="text-xs font-medium text-gray-600 mb-1">Total Sales</div>
        <div id="total-sales" class="text-xl font-bold text-blue-800"></div>
    </div>

    <div class="bg-green-50 border border-green-200 rounded-xl p-4 text-center flex flex-col justify-center min-h-[92px]">
        <div class="text-xs font-medium text-gray-600 mb-1">Previous Period</div>
        <div id="previous-total" class="text-xl font-bold text-green-800"></div>
    </div>

    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center flex flex-col justify-center min-h-[92px]">
        <div class="text-xs font-medium text-gray-600 mb-1">Growth Rate</div>
        <div id="growth-rate-wrapper" class="text-xl font-bold flex items-center justify-center whitespace-nowrap">
            <span id="growth-icon" class="mr-1"></span>
            <span id="growth-rate-value"></span>
        </div>
    </div>

    <div class="bg-purple-50 border border-purple-200 rounded-xl p-4 text-center flex flex-col justify-center min-h-[92px]">
        <div class="text-xs font-medium text-gray-600 mb-1">Daily Average</div>
        <div id="daily-average" class="text-xl font-bold text-purple-800"></div>
    </div>
</div>

{{-- Sales Trend chart (dominant) + Report Shortcuts (narrow) --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

    {{-- Sales Trend --}}
    <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-gray-300">
        <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
            <h3 class="text-base font-bold text-gray-900">Sales Trend</h3>
            <div class="flex flex-wrap gap-2">
                <button id="btn-rolling30" onclick="dashboardApp.changeSalesPeriod('rolling30')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Rolling 30 Days</button>
                <button id="btn-mtd" onclick="dashboardApp.changeSalesPeriod('mtd')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Month to Date</button>
                <button id="btn-lastMonth" onclick="dashboardApp.changeSalesPeriod('lastMonth')"
                    class="px-4 py-2 rounded-lg text-sm font-medium transition-colors">Last Month</button>
            </div>
        </div>
        <div id="salesChart" class="chart-container" style="height: 380px;"></div>
    </div>

    {{-- Report Shortcuts --}}
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300">
        <h3 class="text-base font-bold text-gray-900">Report Shortcuts</h3>
        <p class="text-xs text-gray-500 mb-4">Go directly to detailed reports</p>

        <nav class="space-y-1">
            @php
                $shortcuts = [
                    ['label' => 'Sales Trend Report',   'icon' => 'arrow-trending-up',   'url' => $reportRoutes['sales_trend']],
                    ['label' => 'Pure Sales Summary',   'icon' => 'document-chart-bar',  'url' => $reportRoutes['pure_sales']],
                    ['label' => 'Top Products',         'icon' => 'cube',                'url' => $reportRoutes['top_products']],
                    ['label' => 'Top Categories',       'icon' => 'tag',                 'url' => $reportRoutes['top_categories']],
                    ['label' => 'Sales by Store',       'icon' => 'building-storefront', 'url' => $reportRoutes['sales_by_store']],
                    ['label' => 'Employee Performance', 'icon' => 'users',               'url' => $reportRoutes['employee']],
                    ['label' => 'Sales Tax Summary',    'icon' => 'receipt-percent',     'url' => $reportRoutes['sales_tax']],
                ];
            @endphp

            @foreach($shortcuts as $s)
                <a href="{{ $s['url'] }}"
                   class="group flex items-center gap-3 px-3 py-2 rounded-lg hover:bg-gray-50 transition-colors">
                    <span class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center flex-shrink-0">
                        <x-dynamic-component :component="'heroicon-o-' . $s['icon']" class="w-4 h-4 text-gray-500 group-hover:text-indigo-600" />
                    </span>
                    <span class="text-sm font-medium text-gray-700 group-hover:text-gray-900">{{ $s['label'] }}</span>
                    <svg class="w-4 h-4 text-gray-300 ml-auto flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                    </svg>
                </a>
            @endforeach
        </nav>
    </div>
</div>

{{-- Executive preview cards --}}
@php
    $previewCards = [
        ['title' => 'Top Products',         'sub' => 'by Sales Amount', 'icon' => 'cube',                'rows' => $topProducts,         'cta' => 'View Top Products Report',      'url' => $reportRoutes['top_products']],
        ['title' => 'Top Categories',       'sub' => 'by Sales Amount', 'icon' => 'tag',                 'rows' => $topCategories,       'cta' => 'View Top Categories Report',    'url' => $reportRoutes['top_categories']],
        ['title' => 'Sales by Store',       'sub' => 'by Sales Amount', 'icon' => 'building-storefront', 'rows' => $salesByStore,        'cta' => 'View Store Performance Report', 'url' => $reportRoutes['sales_by_store']],
        ['title' => 'Employee Performance', 'sub' => 'by Sales Amount', 'icon' => 'users',               'rows' => $employeePerformance, 'cta' => 'View Employee Report',          'url' => $reportRoutes['employee']],
    ];
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
    @foreach($previewCards as $card)
        <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 flex flex-col">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                    <x-dynamic-component :component="'heroicon-o-' . $card['icon']" class="w-4 h-4 text-indigo-600" />
                </div>
                <div>
                    <h3 class="text-sm font-bold text-gray-900 leading-tight">{{ $card['title'] }}</h3>
                    <p class="text-[11px] text-gray-500">{{ $card['sub'] }}</p>
                </div>
            </div>

            <ul class="space-y-2.5 flex-1">
                @forelse($card['rows'] as $row)
                    <li class="flex items-center justify-between gap-2 text-sm">
                        <span class="text-gray-700 truncate">{{ $row['name'] }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums whitespace-nowrap">
                            {{ \App\Helpers\CustomHelper::formatCurrency($row['revenue']) }}
                        </span>
                    </li>
                @empty
                    <li class="text-sm text-gray-400">No data for this period</li>
                @endforelse
            </ul>

            <a href="{{ $card['url'] }}"
               class="mt-4 inline-flex items-center gap-1 text-sm font-medium text-indigo-600 hover:text-indigo-700">
                {{ $card['cta'] }}
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>
    @endforeach
</div>

{{-- Footer note --}}
<p class="text-center text-xs text-gray-400 mt-6">
    All financial data is provided by the Sales Reporting Engine to ensure accuracy and consistency across all modules.
</p>
