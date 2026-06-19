@extends('admin.layouts.app')

@section('title', 'Pure Sales Summary')

@push('css')
@endpush

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Pure Sales Summary</h3>
        </div>
        <a href="{{ route('admin.reports.sales-reports.pure-sales-summary.export', request()->query()) }}"
           class="inline-flex items-center gap-2 px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
            <x-heroicon-o-arrow-down-tray class="w-4 h-4" />
            Export CSV
        </a>
    </div>

    {{-- ── FILTERS ─────────────────────────────────────────────────────────── --}}
    <div id="filter-panel" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm">

        {{-- Panel header --}}
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <x-heroicon-o-funnel class="w-4 h-4 text-gray-500 dark:text-gray-400" />
                <span>Filters</span>
            </div>
            <button type="button" id="btn-clear-filters"
                class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 flex gap-1.5 items-center rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                Clear Filters
            </button>
        </div>

        {{-- Row 1: Main dropdowns --}}
        <div class="flex flex-wrap items-end gap-3">

            {{-- Date Range Preset --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                <select id="f-date-range"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value=""           @selected(($filters['date_range'] ?? '') === '')>All Time</option>
                    <option value="today"      @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="yesterday"  @selected(($filters['date_range'] ?? '') === 'yesterday')>Yesterday</option>
                    <option value="last_7"     @selected(($filters['date_range'] ?? '') === 'last_7')>Last 7 Days</option>
                    <option value="last_30"    @selected(($filters['date_range'] ?? '') === 'last_30')>30 Day Rolling</option>
                    <option value="mtd"        @selected(($filters['date_range'] ?? 'mtd') === 'mtd')>Month to Date</option>
                    <option value="qtd"        @selected(($filters['date_range'] ?? '') === 'qtd')>Quarter to Date</option>
                    <option value="ytd"        @selected(($filters['date_range'] ?? '') === 'ytd')>Year to Date</option>
                    <option value="custom"     @selected(($filters['date_range'] ?? '') === 'custom')>Custom Range</option>
                </select>
            </div>

            {{-- Custom date inputs (shown only when custom is selected) --}}
            <div id="custom-date-wrap" class="{{ ($filters['date_range'] ?? '') === 'custom' ? '' : 'hidden' }} flex gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Start</label>
                    {!! html()->text('start_date', old('start_date', $filters['start_date'] ?? ''))->class([
                        'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 datepicker dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    ])->attributes(['id' => 'f-start-date', 'placeholder' => 'Start Date', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">End</label>
                    {!! html()->text('end_date', old('end_date', $filters['end_date'] ?? ''))->class([
                        'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 datepicker dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    ])->attributes(['id' => 'f-end-date', 'placeholder' => 'End Date', 'autocomplete' => 'off']) !!}
                </div>
            </div>

            {{-- Store --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Store</label>
                <select id="f-store"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(($filters['store'] ?? '') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Item Type --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Item Type</label>
                <select id="f-item-type"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all"     @selected(($filters['item_type'] ?? 'all') === 'all')>All Items</option>
                    <option value="Rental"  @selected(($filters['item_type'] ?? '') === 'Rental')>Rental</option>
                    <option value="Retail"  @selected(($filters['item_type'] ?? '') === 'Retail')>Retail</option>
                    <option value="Service" @selected(($filters['item_type'] ?? '') === 'Service')>Service</option>
                    <option value="Fee"     @selected(($filters['item_type'] ?? '') === 'Fee')>Fee</option>
                    <option value="Other"   @selected(($filters['item_type'] ?? '') === 'Other')>Other</option>
                </select>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Category</label>
                <select id="f-category"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(($filters['category'] ?? '') == $cat->id)>{{ $cat->title }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Product (dynamically filtered by category) --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Product</label>
                <select id="f-product"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white min-w-[160px]">
                    <option value="">All Products</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(($filters['product'] ?? '') == $prod->id)>{{ $prod->product_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sale Type segmented toggle --}}
            @php $saleType = $filters['sale_type'] ?? 'all'; @endphp
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sale Type</label>
                <div id="f-sale-type-group" class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
                    @foreach (['all' => 'All Sales', 'rental' => 'Rental', 'retail' => 'Retail'] as $val => $label)
                    <button type="button" data-value="{{ $val }}"
                        class="sale-type-btn px-4 py-2 font-medium transition-colors
                            {{ $val !== 'all' ? 'border-l border-gray-300 dark:border-gray-600' : '' }}
                            {{ $saleType === $val
                                ? 'bg-brand-500 text-white'
                                : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" id="f-sale-type" value="{{ $saleType }}">
            </div>

        </div>

        {{-- Row 2: Checkbox inclusion/exclusion filters --}}
        <div class="flex flex-wrap gap-x-5 gap-y-2 mt-4 pt-3 border-t border-gray-100 dark:border-gray-700 items-center">

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-exclude-damage-waiver"
                    {{ ($filters['damage_waiver'] ?? '') === 'exclude' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Exclude Damage Waiver
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-damage-waiver-only"
                    {{ ($filters['damage_waiver'] ?? '') === 'only' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Damage Waiver Only
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-exclude-track-ins"
                    {{ ($filters['track_insurance'] ?? '') === 'exclude' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Exclude Track Ins.
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-track-ins-only"
                    {{ ($filters['track_insurance'] ?? '') === 'only' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Track Ins. Only
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-exclude-delivery"
                    {{ ($filters['delivery'] ?? '') === 'exclude' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Exclude Delivery
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-delivery-only"
                    {{ ($filters['delivery'] ?? '') === 'only' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Delivery Only
            </label>

            <label class="flex items-center gap-1.5 text-sm text-gray-700 dark:text-gray-300 cursor-pointer select-none">
                <input type="checkbox" id="f-exclude-shipping"
                    {{ ($filters['shipping'] ?? '') === 'exclude' ? 'checked' : '' }}
                    class="rounded border-gray-300 text-brand-500 focus:ring-brand-500">
                Exclude Shipping
            </label>

        </div>
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────────── --}}
    <div id="kpi-section">
        @include('admin.reports.sales_reports.pure_sales_summary.partials._kpi_cards', [
            'kpis'           => $kpis,
            'dateRangeLabel' => $dateRangeLabel,
        ])
    </div>

    {{-- ── SALES TREND CHART ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-3 flex-wrap">
            Sales Trend Analysis
            <span id="psr-chart-period-badge"
                  class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300 tracking-wide">
                <span id="psr-chart-period">{{ $dateRangeLabel }}</span>
            </span>
        </h2>

        {{-- Metric cards --}}
        <div class="flex justify-center mb-6">
            <div class="flex flex-wrap justify-center gap-4">
                <div class="bg-blue-50 p-4 rounded-xl border border-blue-200 min-w-[168px] text-center">
                    <div class="text-xs text-gray-600 font-medium mb-1">Total Sales</div>
                    <div id="psr-total-sales" class="text-lg font-semibold text-blue-800">—</div>
                </div>
                <div class="bg-green-50 p-4 rounded-xl border border-green-200 min-w-[168px] text-center">
                    <div class="text-xs text-gray-600 font-medium mb-1">Previous Period</div>
                    <div id="psr-previous-total" class="text-lg font-semibold text-green-800">—</div>
                </div>
                <div class="bg-emerald-50 p-4 rounded-xl border border-emerald-200 min-w-[168px] text-center">
                    <div class="text-xs text-gray-600 font-medium mb-1">Growth Rate</div>
                    <div id="psr-growth-wrapper" class="text-lg font-semibold flex items-center justify-center whitespace-nowrap">
                        <span id="psr-growth-icon" class="mr-1"></span>
                        <span id="psr-growth-value">—</span>
                    </div>
                </div>
                <div class="bg-purple-50 p-4 rounded-xl border border-purple-200 min-w-[168px] text-center">
                    <div class="text-xs text-gray-600 font-medium mb-1">Daily Average</div>
                    <div id="psr-daily-avg" class="text-lg font-semibold text-purple-800">—</div>
                </div>
                <div class="bg-gray-50 p-4 rounded-xl border border-gray-200 min-w-[168px] text-center">
                    <div class="text-xs text-gray-600 font-medium mb-1">Transactions</div>
                    <div id="psr-transactions" class="text-lg font-semibold text-gray-800">{{ $kpis['transaction_count'] }}</div>
                    <div class="text-xs text-gray-400 mt-0.5">Avg: <span id="psr-avg-ticket">{{ $kpis['average_ticket'] }}</span></div>
                </div>
            </div>
        </div>

        <div id="psr-no-data" class="hidden text-center py-12 text-gray-400 text-sm">
            Select a date range to view the sales trend.
        </div>
        <div id="psr-sales-chart" style="min-height: 350px;"></div>
    </div>

    {{-- ── DETAIL GRID ──────────────────────────────────────────────────────── --}}
     {{--<div id="detail-section">
        @include('admin.reports.sales_reports.pure_sales_summary.partials._detail_table', [
            'grid' => $grid,
        ])
    </div>--}}

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE = '{{ route('admin.reports.sales-reports.pure-sales-summary.index') }}';
    const EXPORT_ROUTE = '{{ route('admin.reports.sales-reports.pure-sales-summary.export') }}';

    // ── Initial trend data from server ────────────────────────────────────────
    const initialTrend = @json($trend);

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fDateRange           = document.getElementById('f-date-range');
    const fStartDate           = document.getElementById('f-start-date');
    const fEndDate             = document.getElementById('f-end-date');
    const fStore               = document.getElementById('f-store');
    const fItemType            = document.getElementById('f-item-type');
    const fCategory            = document.getElementById('f-category');
    const fProduct             = document.getElementById('f-product');
    const fExcludeDamageWaiver = document.getElementById('f-exclude-damage-waiver');
    const fDamageWaiverOnly    = document.getElementById('f-damage-waiver-only');
    const fExcludeTrackIns     = document.getElementById('f-exclude-track-ins');
    const fTrackInsOnly        = document.getElementById('f-track-ins-only');
    const fExcludeDelivery     = document.getElementById('f-exclude-delivery');
    const fDeliveryOnly        = document.getElementById('f-delivery-only');
    const fExcludeShipping     = document.getElementById('f-exclude-shipping');
    const customDateWrap       = document.getElementById('custom-date-wrap');
    const fSaleType            = document.getElementById('f-sale-type');
    const fSaleTypeGroup       = document.getElementById('f-sale-type-group');

    // ── Helpers ───────────────────────────────────────────────────────────────
    function collectFilters() {
        const params = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) params.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate.value) params.set('start_date', fStartDate.value);
            if (fEndDate.value)   params.set('end_date',   fEndDate.value);
        }
        if (fStore.value)              params.set('store', fStore.value);
        if (fItemType.value !== 'all') params.set('item_type', fItemType.value);
        if (fCategory.value)           params.set('category', fCategory.value);
        if (fProduct.value)            params.set('product', fProduct.value);

        // Damage Waiver (mutually exclusive pair)
        if (fExcludeDamageWaiver.checked)     params.set('damage_waiver', 'exclude');
        else if (fDamageWaiverOnly.checked)   params.set('damage_waiver', 'only');

        // Track Insurance (mutually exclusive pair)
        if (fExcludeTrackIns.checked)         params.set('track_insurance', 'exclude');
        else if (fTrackInsOnly.checked)       params.set('track_insurance', 'only');

        // Delivery (mutually exclusive pair)
        if (fExcludeDelivery.checked)         params.set('delivery', 'exclude');
        else if (fDeliveryOnly.checked)       params.set('delivery', 'only');

        // Shipping (single exclusion)
        if (fExcludeShipping.checked)         params.set('shipping', 'exclude');

        // Sale Type
        const st = fSaleType.value;
        if (st && st !== 'all') params.set('sale_type', st);

        return params;
    }

    function setLoading(on) {
        const kpi    = document.getElementById('kpi-section');
        const detail = document.getElementById('detail-section');
        if (kpi)    kpi.style.opacity    = on ? '0.4' : '1';
        if (detail) detail.style.opacity = on ? '0.4' : '1';
    }

    // ── Run Report ─────────────────────────────────────────────────────────────
    function runReport(page) {
        const params = collectFilters();
        if (page && page > 1) params.set('page', page);

        setLoading(true);

        fetch(ROUTE + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) throw new Error(data.error || 'Report failed');
            const kpiSec    = document.getElementById('kpi-section');
            const detailSec = document.getElementById('detail-section');
            if (data.kpi_html && kpiSec)    kpiSec.innerHTML    = data.kpi_html;
            if (data.html     && detailSec) detailSec.innerHTML = data.html;
            if (data.trend)                 renderTrend(data.trend);
            if (data.date_range_label) {
                const periodEl = document.getElementById('psr-chart-period');
                if (periodEl) periodEl.textContent = data.date_range_label;
            }
            if (data.kpis) {
                const txEl  = document.getElementById('psr-transactions');
                const avgEl = document.getElementById('psr-avg-ticket');
                if (txEl)  txEl.textContent  = data.kpis.transaction_count  ?? '—';
                if (avgEl) avgEl.textContent  = data.kpis.average_ticket     ?? '—';
            }
            document.querySelectorAll('a[href*="pure-sales-summary/export"]').forEach(a => {
                a.href = EXPORT_ROUTE + '?' + params.toString();
            });
        })
        .catch(err => {
            console.error('Sales report error:', err);
            alert('Report failed: ' + err.message);
        })
        .finally(() => setLoading(false));
    }

    // ── Currency formatter ────────────────────────────────────────────────────
    function formatCurrency(val) {
        const code = window.APP_CURRENCY || 'USD';
        return new Intl.NumberFormat('en-US', {
            style: 'currency', currency: code,
            minimumFractionDigits: 0, maximumFractionDigits: 0,
        }).format(val || 0);
    }

    // ── Trend chart ───────────────────────────────────────────────────────────
    let psrChart = null;

    function buildChartOptions(trend) {
        return {
            series: [
                { name: 'Current Period', data: trend.current },
                { name: 'Previous Period', data: trend.previous },
            ],
            chart: {
                height: 350,
                type: 'line',
                toolbar: { show: false },
                zoom:    { enabled: false },
            },
            stroke:  { curve: 'smooth', width: [2, 2] },
            colors:  ['#3B82F6', '#10B981'],
            markers: { size: 0 },
            xaxis: {
                categories: trend.categories,
                tickAmount: Math.min(trend.categories.length, 15),
                labels:     { rotate: -30, style: { fontSize: '11px' } },
            },
            yaxis: {
                labels: { formatter: v => formatCurrency(v) },
            },
            tooltip: {
                y: { formatter: v => formatCurrency(v) },
            },
            legend: { position: 'top' },
            grid:   { borderColor: '#f3f4f6' },
        };
    }

    function updateTrendMetrics(trend) {
        document.getElementById('psr-total-sales').textContent    = formatCurrency(trend.totalSales);
        document.getElementById('psr-previous-total').textContent = formatCurrency(trend.previousTotalSales);
        document.getElementById('psr-daily-avg').textContent      = formatCurrency(trend.dailyAverage);

        const wrapper    = document.getElementById('psr-growth-wrapper');
        const growthVal  = document.getElementById('psr-growth-value');
        const growthIcon = document.getElementById('psr-growth-icon');

        growthVal.textContent = Math.abs(trend.growthRate).toFixed(1) + '%';
        wrapper.classList.remove('text-emerald-800', 'text-red-800');
        wrapper.classList.add(trend.growthRate >= 0 ? 'text-emerald-800' : 'text-red-800');

        if (trend.growthRate >= 0) {
            growthIcon.innerHTML = `<svg fill="currentColor" class="w-5 h-5" viewBox="0 0 640 640"><path d="M416 224C398.3 224 384 209.7 384 192C384 174.3 398.3 160 416 160L576 160C593.7 160 608 174.3 608 192L608 352C608 369.7 593.7 384 576 384C558.3 384 544 369.7 544 352L544 269.3L374.6 438.7C362.1 451.2 341.8 451.2 329.3 438.7L224 333.3L86.6 470.6C74.1 483.1 53.8 483.1 41.3 470.6C28.8 458.1 28.8 437.8 41.3 425.3L201.3 265.3C213.8 252.8 234.1 252.8 246.6 265.3L352 370.7L498.7 224L416 224z"/></svg>`;
        } else {
            growthIcon.innerHTML = `<svg fill="currentColor" class="w-5 h-5" viewBox="0 0 640 640"><path d="M416 416C398.3 416 384 430.3 384 448C384 465.7 398.3 480 416 480L576 480C593.7 480 608 465.7 608 448L608 288C608 270.3 593.7 256 576 256C558.3 256 544 270.3 544 288L544 370.7L374.6 201.3C362.1 188.8 341.8 188.8 329.3 201.3L224 306.7L86.6 169.4C74.1 156.9 53.8 156.9 41.3 169.4C28.8 181.9 28.8 202.2 41.3 214.7L201.3 374.7C213.8 387.2 234.1 387.2 246.6 374.7L352 269.3L498.7 416L416 416z"/></svg>`;
        }
    }

    function renderTrend(trend) {
        const noData   = document.getElementById('psr-no-data');
        const chartEl  = document.getElementById('psr-sales-chart');

        if (!trend || !trend.categories || trend.categories.length === 0) {
            noData.classList.remove('hidden');
            chartEl.style.display = 'none';
            return;
        }

        noData.classList.add('hidden');
        chartEl.style.display = '';

        updateTrendMetrics(trend);

        if (!psrChart) {
            psrChart = new ApexCharts(chartEl, buildChartOptions(trend));
            psrChart.render();
        } else {
            psrChart.updateOptions({ xaxis: { categories: trend.categories, tickAmount: Math.min(trend.categories.length, 15) } }, false, false);
            psrChart.updateSeries([
                { name: 'Current Period',  data: trend.current },
                { name: 'Previous Period', data: trend.previous },
            ]);
        }
    }

    // ── Debounced auto-run ─────────────────────────────────────────────────────
    let debounceTimer = null;
    function scheduleRun() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => runReport(1), 400);
    }

    // ── Custom date range toggle ───────────────────────────────────────────────
    fDateRange.addEventListener('change', function () {
        customDateWrap.classList.toggle('hidden', this.value !== 'custom');
        scheduleRun();
    });

    // ── Dropdown filter changes ────────────────────────────────────────────────
    [fStore, fItemType, fProduct].forEach(el => {
        if (el) el.addEventListener('change', scheduleRun);
    });

    // ── Category → Product dependency (then auto-run) ─────────────────────────
    fCategory.addEventListener('change', function () {
        const catId = this.value;
        fProduct.innerHTML = '<option value="">All Products</option>';

        const afterProducts = () => scheduleRun();

        if (!catId) { afterProducts(); return; }

        fetch(ROUTE + '?ajax_products=1&category=' + catId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.products) {
                data.products.forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.product_name;
                    fProduct.appendChild(opt);
                });
            }
            afterProducts();
        })
        .catch(err => { console.error('Product load error:', err); afterProducts(); });
    });

    // ── Checkbox pairs (mutually exclusive) ────────────────────────────────────
    function bindCheckboxPair(cbA, cbB) {
        cbA.addEventListener('change', function () {
            if (this.checked) cbB.checked = false;
            scheduleRun();
        });
        cbB.addEventListener('change', function () {
            if (this.checked) cbA.checked = false;
            scheduleRun();
        });
    }

    bindCheckboxPair(fExcludeDamageWaiver, fDamageWaiverOnly);
    bindCheckboxPair(fExcludeTrackIns,     fTrackInsOnly);
    bindCheckboxPair(fExcludeDelivery,     fDeliveryOnly);
    fExcludeShipping.addEventListener('change', scheduleRun);

    // ── Sale Type toggle ───────────────────────────────────────────────────────
    function setSaleType(val) {
        fSaleType.value = val;
        fSaleTypeGroup.querySelectorAll('.sale-type-btn').forEach(btn => {
            const active = btn.dataset.value === val;
            btn.classList.toggle('bg-brand-500', active);
            btn.classList.toggle('text-white',   active);
            btn.classList.toggle('bg-white',     !active);
            btn.classList.toggle('dark:bg-gray-700', !active);
            btn.classList.toggle('text-gray-700', !active);
            btn.classList.toggle('dark:text-gray-200', !active);
        });
    }
    fSaleTypeGroup.addEventListener('click', function (e) {
        const btn = e.target.closest('.sale-type-btn');
        if (!btn) return;
        setSaleType(btn.dataset.value);
        scheduleRun();
    });

    // ── Clear filters ──────────────────────────────────────────────────────────
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fDateRange.value               = 'mtd';
        fStartDate.value               = '';
        fEndDate.value                 = '';
        fStore.value                   = '';
        fItemType.value                = 'all';
        fCategory.value                = '';
        fProduct.innerHTML             = '<option value="">All Products</option>';
        fExcludeDamageWaiver.checked   = false;
        fDamageWaiverOnly.checked      = false;
        fExcludeTrackIns.checked       = false;
        fTrackInsOnly.checked          = false;
        fExcludeDelivery.checked       = false;
        fDeliveryOnly.checked          = false;
        fExcludeShipping.checked       = false;
        setSaleType('all');
        customDateWrap.classList.add('hidden');
        runReport(1);
    });

    // ── Delegate pagination clicks ─────────────────────────────────────────────
    document.addEventListener('click', function (e) {
        const link = e.target.closest('[data-page], nav a');
        if (!link || !link.href || !link.href.includes('page=')) return;
        const url  = new URL(link.href);
        const page = url.searchParams.get('page');
        if (page) {
            e.preventDefault();
            runReport(parseInt(page, 10));
        }
    });

    // ── Init chart on page load ────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => renderTrend(initialTrend));

})();
</script>
@endpush
