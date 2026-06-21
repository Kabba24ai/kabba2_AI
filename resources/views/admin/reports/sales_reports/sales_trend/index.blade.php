@extends('admin.layouts.app')

@section('title', 'Sales Trend Analysis')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- ── Page Header ────────────────────────────────────────────────────── --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Sales Trend Analysis</h3>
        </div>
    </div>

    {{-- ── Filters ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm">

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-200">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z" />
                </svg>
                <span>Filters</span>
            </div>
            <button type="button" id="btn-clear-filters"
                class="text-sm text-gray-600 dark:text-gray-300 bg-white dark:bg-gray-700 px-3 py-1.5 flex gap-1.5 items-center rounded-md border border-gray-300 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-600 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
                Clear
            </button>
        </div>

        {{-- Row 1: Year + Compare + Store + Sale Type + Category + Product --}}
        <div class="flex flex-wrap items-end gap-3">

            {{-- Primary Year --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Year</label>
                <select id="f-year"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @foreach ($availableYears as $yr)
                        <option value="{{ $yr }}" @selected($yr === $primaryYear)>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Compare Year 1 --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Compare Year 1</label>
                <select id="f-compare-1"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">— None —</option>
                    @foreach (array_reverse($availableYears) as $yr)
                        <option value="{{ $yr }}" @selected(isset($compareYears[0]) && $compareYears[0] === $yr)>{{ $yr }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Compare Year 2 --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Compare Year 2</label>
                <select id="f-compare-2"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">— None —</option>
                    @foreach (array_reverse($availableYears) as $yr)
                        <option value="{{ $yr }}" @selected(isset($compareYears[1]) && $compareYears[1] === $yr)>{{ $yr }}</option>
                    @endforeach
                </select>
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

            {{-- Sale Type --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sale Type</label>
                <div id="f-sale-type-group" class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
                    @foreach (['all' => 'All', 'rental' => 'Rental', 'retail' => 'Retail'] as $val => $label)
                        <button type="button" data-value="{{ $val }}"
                            class="sale-type-btn px-3 py-2 font-medium transition-colors
                                {{ $val !== 'all' ? 'border-l border-gray-300 dark:border-gray-600' : '' }}
                                {{ ($filters['sale_type'] ?? 'all') === $val
                                    ? 'bg-blue-600 text-white'
                                    : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <input type="hidden" id="f-sale-type" value="{{ $filters['sale_type'] ?? 'all' }}">
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

            {{-- Product --}}
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

        </div>
    </div>

    {{-- ── KPI Cards ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 gap-4 mb-6" id="kpi-section">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">YTD Net Sales</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white" id="kpi-ytd">—</p>
            <p class="text-xs text-gray-400 mt-0.5" id="kpi-ytd-label">—</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1" id="kpi-prior-label">Prior Year</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white" id="kpi-prior">—</p>
            <p class="text-xs text-gray-400 mt-0.5">Same period</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">$ Change</p>
            <p class="text-lg font-bold" id="kpi-dollar-change">—</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">% Change</p>
            <p class="text-lg font-bold" id="kpi-pct-change">—</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Best Month</p>
            <p class="text-lg font-bold text-emerald-600" id="kpi-best-month">—</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Worst Month</p>
            <p class="text-lg font-bold text-red-500" id="kpi-worst-month">—</p>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">Avg / Month</p>
            <p class="text-lg font-bold text-gray-900 dark:text-white" id="kpi-avg">—</p>
        </div>

    </div>

    {{-- ── Primary Chart — single year monthly bar ────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white" id="primary-chart-title">Monthly Net Sales</h2>
                <p class="text-xs text-gray-400 mt-0.5">January — December</p>
            </div>
        </div>
        <div id="primary-chart-no-data" class="hidden text-center py-14 text-gray-400 text-sm">No data for selected filters.</div>
        <div id="primary-chart" style="min-height:300px;"></div>
    </div>

    {{-- ── Secondary Chart — YOY grouped bar ──────────────────────────────── --}}
    <div id="yoy-section" class="hidden">

        {{-- YOY KPI summary --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-4" id="yoy-kpi-row"></div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Year-Over-Year Comparison</h2>
                <p class="text-xs text-gray-400 mt-0.5">Monthly Net Sales — side-by-side bars per month</p>
            </div>
            <div id="yoy-chart-no-data" class="hidden text-center py-14 text-gray-400 text-sm">Select at least one comparison year.</div>
            <div id="yoy-chart" style="min-height:320px;"></div>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE      = @json(route('admin.reports.sales-reports.sales-trend.index'));
    const initData   = @json($data);

    // ── Chart colors (primary, compare1, compare2) ────────────────────────────
    const YEAR_COLORS = ['#3B82F6', '#F97316', '#10B981'];

    // ── State ─────────────────────────────────────────────────────────────────
    let reportData     = null;
    let primaryChart   = null;
    let yoyChart       = null;
    let debounceTimer  = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fYear           = document.getElementById('f-year');
    const fCompare1       = document.getElementById('f-compare-1');
    const fCompare2       = document.getElementById('f-compare-2');
    const fStore          = document.getElementById('f-store');
    const fSaleType       = document.getElementById('f-sale-type');
    const fSaleTypeGroup  = document.getElementById('f-sale-type-group');
    const fCategory       = document.getElementById('f-category');
    const fProduct        = document.getElementById('f-product');
    const kpiSection      = document.getElementById('kpi-section');
    const yoySection      = document.getElementById('yoy-section');

    // ── Formatters ────────────────────────────────────────────────────────────
    const currFmt = new Intl.NumberFormat('en-US', {
        style: 'currency', currency: window.APP_CURRENCY || 'USD',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    });
    function fmt(v)    { return currFmt.format(v || 0); }
    function fmtSigned(v) {
        const s = currFmt.format(Math.abs(v || 0));
        return v >= 0 ? '+' + s : '−' + s;
    }

    // ── Collect compare years from dropdowns ──────────────────────────────────
    function getCompareYears() {
        const years = [];
        const primaryYear = parseInt(fYear.value, 10);
        [fCompare1, fCompare2].forEach(sel => {
            const v = parseInt(sel.value, 10);
            if (sel.value && v !== primaryYear && !years.includes(v)) {
                years.push(v);
            }
        });
        return years;
    }

    // ── Collect all filter values for the AJAX request ────────────────────────
    function collectFilters() {
        const p = new URLSearchParams();
        p.set('year', fYear.value);
        getCompareYears().forEach(y => p.append('compare_years[]', y));
        if (fStore.value)         p.set('store', fStore.value);
        const st = fSaleType.value;
        if (st && st !== 'all')   p.set('sale_type', st);
        if (fCategory.value)      p.set('category', fCategory.value);
        if (fProduct.value)       p.set('product', fProduct.value);
        return p;
    }

    // ── Fetch report data ─────────────────────────────────────────────────────
    function runReport() {
        const params = collectFilters();
        kpiSection.style.opacity = '0.4';

        fetch(ROUTE + '?' + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(resp => {
            if (!resp.success) throw new Error(resp.error || 'Report failed');
            reportData = resp.data;
            renderAll(reportData);
        })
        .catch(err => {
            console.error('[SalesTrend] Error:', err);
            alert('Report failed: ' + err.message);
        })
        .finally(() => {
            kpiSection.style.opacity = '1';
        });
    }

    function scheduleRun() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runReport, 400);
    }

    // ── Render everything from data ───────────────────────────────────────────
    function renderAll(data) {
        renderKpis(data);
        renderPrimaryChart(data);
        renderYoySection(data);
    }

    // ── KPI Cards ─────────────────────────────────────────────────────────────
    function renderKpis(data) {
        const k = data.kpis;
        const primaryYear = data.primary_year;

        document.getElementById('kpi-ytd').textContent    = fmt(k.ytd_net_sales);
        document.getElementById('kpi-ytd-label').textContent =
            k.months_counted < 12
                ? 'Jan–' + data.months[k.months_counted - 1] + ' ' + primaryYear
                : '' + primaryYear;

        document.getElementById('kpi-prior-label').textContent = k.prior_year + ' Same Period';
        document.getElementById('kpi-prior').textContent = fmt(k.prior_year_same_period);

        const dcEl = document.getElementById('kpi-dollar-change');
        dcEl.textContent  = fmtSigned(k.dollar_change);
        dcEl.className    = 'text-lg font-bold ' + (k.dollar_change >= 0 ? 'text-emerald-600' : 'text-red-500');

        const pcEl = document.getElementById('kpi-pct-change');
        const pctStr = (k.pct_change >= 0 ? '+' : '') + k.pct_change.toFixed(1) + '%';
        pcEl.textContent = pctStr;
        pcEl.className   = 'text-lg font-bold ' + (k.pct_change >= 0 ? 'text-emerald-600' : 'text-red-500');

        document.getElementById('kpi-best-month').textContent  = k.best_month;
        document.getElementById('kpi-worst-month').textContent = k.worst_month;
        document.getElementById('kpi-avg').textContent         = fmt(k.avg_monthly_net_sales);
    }

    // ── Primary Chart ─────────────────────────────────────────────────────────
    function renderPrimaryChart(data) {
        const noData = document.getElementById('primary-chart-no-data');
        const el     = document.getElementById('primary-chart');

        // Primary series is always data.series[0]
        const primarySeries = data.series[0] ?? null;
        const allZero = !primarySeries || primarySeries.data.every(v => v === 0);

        document.getElementById('primary-chart-title').textContent =
            data.primary_year + ' — Monthly Net Sales';

        if (allZero) {
            noData.classList.remove('hidden');
            el.style.display = 'none';
            return;
        }
        noData.classList.add('hidden');
        el.style.display = '';

        const opts = {
            series: [{ name: String(data.primary_year), data: primarySeries.data }],
            chart:  { type: 'bar', height: 300, toolbar: { show: false }, animations: { enabled: true, speed: 350 } },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            colors: [YEAR_COLORS[0]],
            dataLabels: { enabled: false },
            xaxis: { categories: data.months },
            yaxis: { labels: { formatter: v => fmt(v) } },
            tooltip: { y: { formatter: v => fmt(v) } },
            grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
        };

        if (!primaryChart) {
            primaryChart = new ApexCharts(el, opts);
            primaryChart.render();
        } else {
            primaryChart.updateOptions({ xaxis: { categories: data.months } }, false, false);
            primaryChart.updateSeries([{ name: String(data.primary_year), data: primarySeries.data }]);
        }
    }

    // ── YOY Section (secondary chart + YOY KPI row) ───────────────────────────
    function renderYoySection(data) {
        const hasCompare = data.compare_years && data.compare_years.length > 0;
        yoySection.classList.toggle('hidden', !hasCompare);
        if (!hasCompare) return;

        renderYoyKpis(data);

        const noData = document.getElementById('yoy-chart-no-data');
        const el     = document.getElementById('yoy-chart');

        const allZero = data.series.every(s => s.data.every(v => v === 0));
        if (allZero) {
            noData.classList.remove('hidden');
            el.style.display = 'none';
            return;
        }
        noData.classList.add('hidden');
        el.style.display = '';

        const series = data.series.map((s, i) => ({
            name: String(s.year),
            data: s.data,
        }));

        const colors = data.series.map((_, i) => YEAR_COLORS[i] ?? '#6B7280');

        const opts = {
            series,
            chart: { type: 'bar', height: 320, toolbar: { show: false }, animations: { enabled: true, speed: 350 } },
            plotOptions: { bar: { borderRadius: 3, columnWidth: '70%', groupPadding: 0.1 } },
            colors,
            dataLabels: { enabled: false },
            xaxis: { categories: data.months },
            yaxis: { labels: { formatter: v => fmt(v) } },
            tooltip: { shared: true, intersect: false, y: { formatter: v => fmt(v) } },
            legend: { position: 'top', horizontalAlign: 'right' },
            grid: { borderColor: '#f3f4f6', strokeDashArray: 4 },
        };

        if (!yoyChart) {
            yoyChart = new ApexCharts(el, opts);
            yoyChart.render();
        } else {
            yoyChart.updateOptions({ xaxis: { categories: data.months }, colors }, false, false);
            yoyChart.updateSeries(series);
        }
    }

    function renderYoyKpis(data) {
        const k    = data.kpis;
        const row  = document.getElementById('yoy-kpi-row');
        const totals = k.yoy_totals ?? [];

        const cards = [
            { label: 'Best Year',          value: String(k.best_year), cls: 'text-emerald-600 text-xl font-bold' },
            { label: 'Best Month Overall', value: k.best_month_overall, cls: 'text-blue-600 text-xl font-bold' },
        ];

        totals.forEach((t, i) => {
            cards.push({ label: t.year + ' Total', value: fmt(t.total), cls: 'text-gray-900 dark:text-white text-xl font-bold' });
        });

        row.innerHTML = cards.map(c => `
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-1">${c.label}</p>
                <p class="${c.cls}">${c.value}</p>
            </div>
        `).join('');
    }

    // ── Compare year dropdown enforcement ────────────────────────────────────
    // Clear a compare dropdown if it matches the primary year.
    function syncCompareOptions() {
        const primaryYear = parseInt(fYear.value, 10);
        [fCompare1, fCompare2].forEach(sel => {
            if (parseInt(sel.value, 10) === primaryYear) {
                sel.value = '';
            }
        });
    }

    [fCompare1, fCompare2].forEach(sel => {
        sel.addEventListener('change', function () {
            syncCompareOptions();
            scheduleRun();
        });
    });

    // ── Sale Type toggle ──────────────────────────────────────────────────────
    function setSaleType(val) {
        fSaleType.value = val;
        fSaleTypeGroup.querySelectorAll('.sale-type-btn').forEach(btn => {
            const on = btn.dataset.value === val;
            btn.classList.toggle('bg-blue-600', on);
            btn.classList.toggle('text-white', on);
            btn.classList.toggle('bg-white', !on);
            btn.classList.toggle('dark:bg-gray-700', !on);
            btn.classList.toggle('text-gray-700', !on);
            btn.classList.toggle('dark:text-gray-200', !on);
        });
    }
    fSaleTypeGroup.addEventListener('click', function (e) {
        const btn = e.target.closest('.sale-type-btn');
        if (!btn) return;
        setSaleType(btn.dataset.value);
        scheduleRun();
    });

    // ── Year change ───────────────────────────────────────────────────────────
    fYear.addEventListener('change', function () {
        syncCompareOptions();
        scheduleRun();
    });

    // ── Category → Product cascade ────────────────────────────────────────────
    fCategory.addEventListener('change', function () {
        const catId = this.value;
        fProduct.innerHTML = '<option value="">All Products</option>';
        if (!catId) { scheduleRun(); return; }
        fetch(ROUTE + '?ajax_products=1&category=' + catId, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(resp => {
            if (resp.products) {
                resp.products.forEach(p => {
                    const opt       = document.createElement('option');
                    opt.value       = p.id;
                    opt.textContent = p.product_name;
                    fProduct.appendChild(opt);
                });
            }
            scheduleRun();
        })
        .catch(() => scheduleRun());
    });

    // ── Standard filter events ────────────────────────────────────────────────
    [fStore, fProduct].forEach(el => {
        if (el) el.addEventListener('change', scheduleRun);
    });

    // ── Clear filters ─────────────────────────────────────────────────────────
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fYear.value        = fYear.options[0]?.value ?? '';
        fCompare1.value    = '';
        fCompare2.value    = '';
        fStore.value       = '';
        fCategory.value    = '';
        fProduct.innerHTML = '<option value="">All Products</option>';
        setSaleType('all');
        runReport();
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        reportData = initData;
        renderAll(initData);
    });

})();
</script>
@endpush
