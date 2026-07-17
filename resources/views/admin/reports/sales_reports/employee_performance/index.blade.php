@extends('admin.layouts.app')

@section('title', 'Employee Performance')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports › Employee Performance</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Employee Performance</h3>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-3 py-2 max-w-sm">
            Qualified revenue includes paid, account, and converted COD orders. Open POD orders shown as Revenue at Risk only.
        </div>
    </div>

    {{-- ── FILTERS ─────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm">

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

        {{-- Row 1: Date + Store --}}
        <div class="flex flex-wrap items-end gap-3 mb-3">

            {{-- Date Range --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                <select id="f-date-range"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="today"       @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="yesterday"   @selected(($filters['date_range'] ?? '') === 'yesterday')>Yesterday</option>
                    <option value="this_week"   @selected(($filters['date_range'] ?? '') === 'this_week')>This Week</option>
                    <option value="last_week"   @selected(($filters['date_range'] ?? '') === 'last_week')>Last Week</option>
                    <option value="mtd"         @selected(($filters['date_range'] ?? 'mtd') === 'mtd')>Month to Date</option>
                    <option value="last_month"  @selected(($filters['date_range'] ?? '') === 'last_month')>Last Month</option>
                    <option value="qtd"         @selected(($filters['date_range'] ?? '') === 'qtd')>Quarter to Date</option>
                    <option value="prev_quarter"@selected(($filters['date_range'] ?? '') === 'prev_quarter')>Previous Quarter</option>
                    <option value="ytd"         @selected(($filters['date_range'] ?? '') === 'ytd')>Year to Date</option>
                    <option value="custom"      @selected(($filters['date_range'] ?? '') === 'custom')>Custom Range</option>
                </select>
            </div>

            {{-- Custom date inputs --}}
            <div id="custom-date-wrap" class="{{ ($filters['date_range'] ?? '') === 'custom' ? '' : 'hidden' }} flex gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Start</label>
                    {!! html()->text('start_date', $filters['start_date'] ?? '')->class([
                        'rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 datepicker dark:bg-gray-700 dark:border-gray-600 dark:text-white',
                    ])->attributes(['id' => 'f-start-date', 'placeholder' => 'Start Date', 'autocomplete' => 'off']) !!}
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">End</label>
                    {!! html()->text('end_date', $filters['end_date'] ?? '')->class([
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
                    <option value="all_individually" @selected(($filters['store'] ?? '') === 'all_individually')>All Individually</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(($filters['store'] ?? '') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Sale Type --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sale Type</label>
                <select id="f-sale-type"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all"    @selected(($filters['sale_type'] ?? 'all') === 'all')>All</option>
                    <option value="rental" @selected(($filters['sale_type'] ?? '') === 'rental')>Rental</option>
                    <option value="retail" @selected(($filters['sale_type'] ?? '') === 'retail')>Retail</option>
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

            {{-- Product --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Product</label>
                <select id="f-product"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="">All Products</option>
                    @foreach ($products as $prod)
                        <option value="{{ $prod->id }}" @selected(($filters['product'] ?? '') == $prod->id)>{{ $prod->product_name }}</option>
                    @endforeach
                </select>
            </div>

        </div>

        {{-- Row 2: Employee Comparison --}}
        <div class="flex flex-wrap items-end gap-3 pt-3 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center gap-1 text-xs text-gray-500 dark:text-gray-400 mr-1">
                <x-heroicon-o-user-group class="w-4 h-4" />
                <span>Employee Comparison</span>
                <span class="text-gray-400">(max 3)</span>
            </div>

            @foreach ([['id' => 'f-emp-1', 'key' => 'employee_1', 'label' => 'Primary Employee'], ['id' => 'f-emp-2', 'key' => 'employee_2', 'label' => 'Compare 1'], ['id' => 'f-emp-3', 'key' => 'employee_3', 'label' => 'Compare 2']] as $slot)
                <div>
                    <label class="block text-xs text-gray-500 mb-1">{{ $slot['label'] }}</label>
                    <select id="{{ $slot['id'] }}"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white min-w-[160px]">
                        <option value="">{{ $loop->index === 0 ? 'All Employees' : '— None —' }}</option>
                        @foreach ($employees as $emp)
                            <option value="{{ $emp->id }}" @selected(in_array($emp->id, $employeeIds) && ($employeeIds[array_search($emp->id, $employeeIds)] ?? null) && array_search($emp->id, $employeeIds) === $loop->parent->index)>
                                {{ $emp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endforeach

            <button type="button" id="btn-run"
                class="px-4 py-2 bg-brand-500 text-white rounded-md text-sm font-medium hover:bg-brand-600 transition">
                Run Report
            </button>
        </div>

    </div>

    {{-- ── DATE RANGE LABEL ─────────────────────────────────────────────────── --}}
    <div id="date-range-label" class="text-sm text-gray-500 dark:text-gray-400 mb-4 font-medium">
        {{ $dateRangeLabel }}
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────────── --}}
    {{-- Populated exclusively by buildKpiCards() in the script below — on the
         initial renderAll(reportData) call and on every AJAX Run Report. The
         JS is the single source of truth for KPI-card markup; the previous
         server-side @include referenced a partial that was never committed
         and 500'd whenever report data existed. --}}
    <div id="kpi-section"></div>

    {{-- ── CHARTS ───────────────────────────────────────────────────────────── --}}
    <div id="charts-section" class="{{ empty($data['employees']) ? 'hidden' : '' }}">

        {{-- Charts 1-3: Revenue / Orders / AOV by Employee --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-6">

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Qualified Revenue by Employee</h4>
                <div id="chart-revenue" style="min-height:260px;"></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Orders Closed by Employee</h4>
                <div id="chart-orders" style="min-height:260px;"></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Average Order Value by Employee</h4>
                <div id="chart-aov" style="min-height:260px;"></div>
            </div>

        </div>

        {{-- Chart 4: Monthly Revenue Trend --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                Monthly Revenue Trend — {{ now()->year }}
            </h4>
            <div id="chart-trend" style="min-height:300px;"></div>
        </div>

        {{-- Chart 5: Product Mix (single employee only) --}}
        <div id="chart-5-wrap" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm hidden">
            <div class="flex items-center justify-between mb-3">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300" id="chart-5-title">Product Mix</h4>
                <div class="flex gap-2">
                    <button type="button" id="mix-revenue-btn"
                        class="px-3 py-1 text-xs rounded-md bg-brand-500 text-white font-medium">Revenue</button>
                    <button type="button" id="mix-qty-btn"
                        class="px-3 py-1 text-xs rounded-md bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200 font-medium">Quantity</button>
                </div>
            </div>
            <div id="chart-mix" style="min-height:260px;"></div>
        </div>

        {{-- Chart 6: Store Performance by Employee (all_individually only) --}}
        <div id="chart-6-wrap" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm hidden">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Store Performance by Employee</h4>
            <div id="chart-store" style="min-height:300px;"></div>
        </div>

    </div>

    {{-- ── EMPTY STATE ─────────────────────────────────────────────────────── --}}
    <div id="empty-state" class="{{ !empty($data['employees']) ? 'hidden' : '' }} text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <x-heroicon-o-users class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
        <p class="text-gray-500 dark:text-gray-400 text-sm">No employee data found for this period.</p>
        <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Try a different date range or adjust your filters.</p>
    </div>

    {{-- ── RANKING TABLE ────────────────────────────────────────────────────── --}}
    <div id="table-section" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mt-6 {{ empty($data['employees']) ? 'hidden' : '' }}">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Employee Rankings</h4>
            <span class="text-xs text-gray-400">Sorted by Qualified Revenue</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead id="ranking-thead" class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider"></thead>
                <tbody id="ranking-tbody"></tbody>
            </table>
        </div>
    </div>

    {{-- Loading overlay --}}
    <div id="loading-overlay" class="hidden fixed inset-0 bg-white/60 dark:bg-gray-900/60 z-50 flex items-center justify-center">
        <div class="flex flex-col items-center gap-3">
            <div class="w-8 h-8 border-4 border-brand-500 border-t-transparent rounded-full animate-spin"></div>
            <span class="text-sm text-gray-600 dark:text-gray-300">Loading report…</span>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    // ── Initial server data ───────────────────────────────────────────────────
    let reportData = @json($data);

    // ── DOM handles ──────────────────────────────────────────────────────────
    const fDateRange  = document.getElementById('f-date-range');
    const fStartDate  = document.getElementById('f-start-date');
    const fEndDate    = document.getElementById('f-end-date');
    const fStore      = document.getElementById('f-store');
    const fSaleType   = document.getElementById('f-sale-type');
    const fCategory   = document.getElementById('f-category');
    const fProduct    = document.getElementById('f-product');
    const fEmp1       = document.getElementById('f-emp-1');
    const fEmp2       = document.getElementById('f-emp-2');
    const fEmp3       = document.getElementById('f-emp-3');
    const btnRun      = document.getElementById('btn-run');
    const btnClear    = document.getElementById('btn-clear-filters');
    const overlay     = document.getElementById('loading-overlay');

    // ── Colour palette ───────────────────────────────────────────────────────
    const EMP_COLORS  = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'];

    // ── Formatters ────────────────────────────────────────────────────────────
    const fmt  = v => '$' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtK = v => {
        const n = Number(v || 0);
        if (n >= 1000) return '$' + (n / 1000).toFixed(1) + 'K';
        return '$' + Math.round(n).toLocaleString('en-US');
    };
    const esc  = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

    // ── ApexCharts instances ─────────────────────────────────────────────────
    let charts = {};
    let mixMode = 'revenue';

    // ── Custom date toggle ────────────────────────────────────────────────────
    fDateRange.addEventListener('change', () => {
        const wrap = document.getElementById('custom-date-wrap');
        wrap.classList.toggle('hidden', fDateRange.value !== 'custom');
    });

    // ── Category → Product cascade ────────────────────────────────────────────
    fCategory.addEventListener('change', () => {
        const catId = fCategory.value;
        fetch(`{{ route('admin.reports.sales-reports.employee-performance.index') }}?ajax_products=1&category=${catId}`)
            .then(r => r.json())
            .then(json => {
                fProduct.innerHTML = '<option value="">All Products</option>';
                (json.products || []).forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.product_name;
                    fProduct.appendChild(opt);
                });
            })
            .catch(err => console.error('Product cascade error', err));
    });

    // ── Run report ────────────────────────────────────────────────────────────
    function buildParams() {
        const p = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) p.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate?.value) p.set('start_date', fStartDate.value);
            if (fEndDate?.value)   p.set('end_date',   fEndDate.value);
        }
        if (fStore.value)    p.set('store',     fStore.value);
        if (fSaleType.value) p.set('sale_type', fSaleType.value);
        if (fCategory.value) p.set('category',  fCategory.value);
        if (fProduct.value)  p.set('product',   fProduct.value);

        const empIds = [fEmp1?.value, fEmp2?.value, fEmp3?.value]
            .filter(v => v && parseInt(v) > 0);
        const unique = [...new Set(empIds)].slice(0, 3);
        unique.forEach((id, i) => p.set(`employee_${i + 1}`, id));

        return p;
    }

    function runReport() {
        const params = buildParams();
        overlay.classList.remove('hidden');

        fetch(`{{ route('admin.reports.sales-reports.employee-performance.index') }}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(json => {
            if (!json.success) { console.error('Report error', json.error); return; }
            reportData = json.data;
            if (json.date_range_label) {
                const el = document.getElementById('date-range-label');
                if (el) el.textContent = json.date_range_label;
            }
            renderAll(json.data);
        })
        .catch(err => console.error('Fetch error', err))
        .finally(() => overlay.classList.add('hidden'));
    }

    btnRun.addEventListener('click', runReport);

    // Also trigger on any filter dropdown change (except employees — require Run click)
    [fDateRange, fStore, fSaleType, fCategory, fProduct].forEach(el => {
        if (el) el.addEventListener('change', runReport);
    });

    // ── Clear filters ─────────────────────────────────────────────────────────
    btnClear.addEventListener('click', () => {
        fDateRange.value  = 'mtd';
        if (fStartDate) fStartDate.value = '';
        if (fEndDate)   fEndDate.value   = '';
        fStore.value    = '';
        fSaleType.value = 'all';
        fCategory.value = '';
        fProduct.innerHTML = '<option value="">All Products</option>';
        if (fEmp1) fEmp1.value = '';
        if (fEmp2) fEmp2.value = '';
        if (fEmp3) fEmp3.value = '';
        document.getElementById('custom-date-wrap').classList.add('hidden');
        runReport();
    });

    // ── Render everything ─────────────────────────────────────────────────────
    function renderAll(data) {
        const employees = data.employees || [];
        const hasData   = employees.length > 0;

        document.getElementById('kpi-section').innerHTML = hasData
            ? buildKpiCards(employees, data.store_mode)
            : '';
        document.getElementById('charts-section').classList.toggle('hidden', !hasData);
        document.getElementById('empty-state').classList.toggle('hidden', hasData);
        document.getElementById('table-section').classList.toggle('hidden', !hasData);

        if (!hasData) return;

        renderBarChart('chart-revenue', employees.map(e => e.name),
            [{ name: 'Qualified Revenue', data: employees.map(e => e.qualified_revenue_raw) }],
            v => fmtK(v), v => fmt(v), '$');

        renderBarChart('chart-orders', employees.map(e => e.name),
            [{ name: 'Orders Closed', data: employees.map(e => e.orders_closed_raw) }],
            v => String(Math.round(v)), v => Math.round(v) + ' orders', '');

        renderBarChart('chart-aov', employees.map(e => e.name),
            [{ name: 'Avg Order Value', data: employees.map(e => e.avg_order_value_raw) }],
            v => fmtK(v), v => fmt(v), '$');

        renderTrendChart(data);
        renderMixChart(data);
        renderStoreChart(data);
        renderRankingTable(employees, data.store_mode);
    }

    // ── Chart 1-3: Vertical grouped bar ──────────────────────────────────────
    function renderBarChart(containerId, labels, series, labelFmt, tooltipFmt, prefix) {
        if (charts[containerId]) { charts[containerId].destroy(); }

        charts[containerId] = new ApexCharts(document.getElementById(containerId), {
            chart:   { type: 'bar', height: 260, toolbar: { show: false } },
            series:  series,
            xaxis:   { categories: labels, labels: { style: { fontSize: '12px' } } },
            yaxis:   { labels: { formatter: v => prefix + Math.round(Math.abs(v)).toLocaleString('en-US') } },
            plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%',
                dataLabels: { position: 'top' } } },
            dataLabels: {
                enabled: true,
                formatter: labelFmt,
                offsetY: -20,
                style: { fontSize: '11px', fontWeight: '700', colors: ['#374151'] },
            },
            colors:  EMP_COLORS.slice(0, series.length),
            tooltip: { y: { formatter: tooltipFmt } },
            legend:  { show: series.length > 1 },
            grid:    { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts[containerId].render();
    }

    // ── Chart 4: Monthly Trend line ───────────────────────────────────────────
    function renderTrendChart(data) {
        const months    = data.months || [];
        const employees = data.employees || [];

        if (charts['chart-trend']) { charts['chart-trend'].destroy(); }

        const series = employees.map((emp, i) => ({
            name: emp.name,
            data: emp.monthly || [],
        }));

        charts['chart-trend'] = new ApexCharts(document.getElementById('chart-trend'), {
            chart:  { type: 'line', height: 300, toolbar: { show: false }, zoom: { enabled: false } },
            series: series,
            xaxis:  { categories: months },
            yaxis:  { labels: { formatter: v => '$' + Math.round(Math.abs(v)).toLocaleString('en-US') } },
            stroke: { curve: 'smooth', width: 2 },
            markers: { size: 4 },
            colors: EMP_COLORS,
            tooltip: { y: { formatter: v => fmt(v) } },
            legend: { show: true, position: 'top' },
            grid:   { borderColor: '#e5e7eb', strokeDashArray: 4 },
            noData: { text: 'No data for this period' },
        });
        charts['chart-trend'].render();
    }

    // ── Chart 5: Product Mix (single employee) ────────────────────────────────
    function renderMixChart(data) {
        const employees = data.employees || [];
        const wrap      = document.getElementById('chart-5-wrap');

        // Show only when exactly one employee is selected
        if (employees.length !== 1) {
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');
        const emp = employees[0];
        document.getElementById('chart-5-title').textContent = `Product Mix — ${emp.name}`;

        drawMixChart(emp.categories || [], mixMode);

        document.getElementById('mix-revenue-btn').addEventListener('click', () => {
            mixMode = 'revenue';
            setMixBtn('revenue');
            drawMixChart(emp.categories || [], 'revenue');
        });
        document.getElementById('mix-qty-btn').addEventListener('click', () => {
            mixMode = 'qty';
            setMixBtn('qty');
            drawMixChart(emp.categories || [], 'qty');
        });
    }

    function setMixBtn(active) {
        const rBtn = document.getElementById('mix-revenue-btn');
        const qBtn = document.getElementById('mix-qty-btn');
        rBtn.className = `px-3 py-1 text-xs rounded-md font-medium ${active === 'revenue' ? 'bg-brand-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200'}`;
        qBtn.className = `px-3 py-1 text-xs rounded-md font-medium ${active === 'qty' ? 'bg-brand-500 text-white' : 'bg-gray-200 dark:bg-gray-600 text-gray-700 dark:text-gray-200'}`;
    }

    function drawMixChart(categories, mode) {
        if (charts['chart-mix']) { charts['chart-mix'].destroy(); }

        const labels = categories.map(c => c.category);
        const vals   = categories.map(c => mode === 'qty' ? c.qty : c.revenue);
        const isRev  = mode === 'revenue';

        charts['chart-mix'] = new ApexCharts(document.getElementById('chart-mix'), {
            chart:   { type: 'bar', height: 260, toolbar: { show: false } },
            series:  [{ name: isRev ? 'Revenue' : 'Quantity', data: vals }],
            xaxis:   { categories: labels, labels: { style: { fontSize: '11px' } } },
            yaxis:   { labels: { formatter: v => isRev ? fmtK(v) : Math.round(v).toLocaleString('en-US') } },
            plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%',
                dataLabels: { position: 'top' } } },
            dataLabels: {
                enabled: true,
                formatter: v => isRev ? fmtK(v) : Math.round(v).toLocaleString('en-US'),
                offsetY: -20,
                style: { fontSize: '11px', fontWeight: '700', colors: ['#374151'] },
            },
            colors:  ['#3b82f6'],
            tooltip: { y: { formatter: v => isRev ? fmt(v) : Math.round(v) + ' units' } },
            legend:  { show: false },
            grid:    { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts['chart-mix'].render();
    }

    // ── Chart 6: Store Performance by Employee ────────────────────────────────
    function renderStoreChart(data) {
        const wrap = document.getElementById('chart-6-wrap');

        if (data.store_mode !== 'all_individually' || !data.store_data?.length) {
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');

        const storeData = data.store_data;
        const storeLabels = storeData.map(s => s.store_name);
        const employees   = data.employees || [];

        // Build one series per employee
        const series = employees.map((emp, i) => ({
            name: emp.name,
            data: storeData.map(store => {
                const empRow = (store.employees || []).find(e => e.id === emp.id);
                return empRow ? empRow.revenue_raw : 0;
            }),
        }));

        if (charts['chart-store']) { charts['chart-store'].destroy(); }

        charts['chart-store'] = new ApexCharts(document.getElementById('chart-store'), {
            chart:   { type: 'bar', height: 320, toolbar: { show: false } },
            series:  series,
            xaxis:   { categories: storeLabels },
            yaxis:   { labels: { formatter: v => fmtK(v) } },
            plotOptions: { bar: { horizontal: false, borderRadius: 3, columnWidth: '60%',
                dataLabels: { position: 'top' } } },
            dataLabels: {
                enabled: true,
                formatter: v => fmtK(v),
                offsetY: -20,
                style: { fontSize: '10px', fontWeight: '700', colors: ['#374151'] },
            },
            colors:  EMP_COLORS,
            tooltip: { y: { formatter: v => fmt(v) } },
            legend:  { show: true, position: 'top' },
            grid:    { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts['chart-store'].render();
    }

    // ── KPI Cards ─────────────────────────────────────────────────────────────
    function buildKpiCards(employees, storeMode) {
        // When multiple employees, show aggregate + per-employee breakdown
        // When single, show that employee's cards
        const totals = aggregateTotals(employees);
        const isSingle = employees.length === 1;
        const emp = isSingle ? employees[0] : null;

        const qr  = isSingle ? emp.qualified_revenue : fmt(totals.qualified_revenue);
        const oc  = isSingle ? emp.orders_closed      : Math.round(totals.orders_closed).toLocaleString('en-US');
        const aov = isSingle ? emp.avg_order_value     : (totals.orders_closed > 0 ? fmt(totals.qualified_revenue / totals.orders_closed) : '$0.00');
        const pr  = isSingle ? emp.paid_revenue        : fmt(totals.paid_revenue);
        const ar  = isSingle ? emp.account_revenue     : fmt(totals.account_revenue);
        const podRate = isSingle
            ? emp.pod_rate + '%'
            : (totals.pod_total > 0 ? (totals.pod_converted / totals.pod_total * 100).toFixed(1) + '%' : '—');
        const rar = isSingle ? emp.revenue_at_risk : fmt(totals.revenue_at_risk);

        return `
        <div class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-7 gap-3 mb-6">
            ${kpiCard('Qualified Revenue',      qr,       'text-blue-600',   'bg-blue-50 dark:bg-blue-900/20',   'M12 6v6m0 0v6m0-6h6m-6 0H6')}
            ${kpiCard('Orders Closed',          oc,       'text-green-600',  'bg-green-50 dark:bg-green-900/20', 'M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z')}
            ${kpiCard('Avg Order Value',        aov,      'text-purple-600', 'bg-purple-50 dark:bg-purple-900/20','M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z')}
            ${kpiCard('Paid Revenue',           pr,       'text-emerald-600','bg-emerald-50 dark:bg-emerald-900/20','M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z')}
            ${kpiCard('Account Revenue',        ar,       'text-indigo-600', 'bg-indigo-50 dark:bg-indigo-900/20','M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z')}
            ${kpiCard('POD Conversion Rate',    podRate,  'text-amber-600',  'bg-amber-50 dark:bg-amber-900/20',  'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z')}
            ${kpiCard('Revenue at Risk',        rar,      'text-red-500',    'bg-red-50 dark:bg-red-900/20',      'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z')}
        </div>`;
    }

    function kpiCard(label, value, textColor, bgColor, iconPath) {
        return `
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-3 shadow-sm">
            <div class="flex items-center gap-2 mb-1">
                <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg ${bgColor}">
                    <svg class="w-4 h-4 ${textColor}" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="${iconPath}"/>
                    </svg>
                </span>
                <p class="text-xs text-gray-500 dark:text-gray-400">${esc(label)}</p>
            </div>
            <p class="text-lg font-bold ${textColor}">${esc(String(value))}</p>
        </div>`;
    }

    function aggregateTotals(employees) {
        return employees.reduce((acc, emp) => ({
            qualified_revenue: acc.qualified_revenue + (emp.qualified_revenue_raw || 0),
            orders_closed:     acc.orders_closed     + (emp.orders_closed_raw    || 0),
            paid_revenue:      acc.paid_revenue      + (emp.paid_revenue_raw     || 0),
            account_revenue:   acc.account_revenue   + (emp.account_revenue_raw  || 0),
            pod_total:         acc.pod_total         + (emp.pod_total            || 0),
            pod_converted:     acc.pod_converted     + (emp.pod_converted        || 0),
            revenue_at_risk:   acc.revenue_at_risk   + (emp.revenue_at_risk_raw  || 0),
        }), { qualified_revenue: 0, orders_closed: 0, paid_revenue: 0,
              account_revenue: 0, pod_total: 0, pod_converted: 0, revenue_at_risk: 0 });
    }

    // ── Ranking Table ─────────────────────────────────────────────────────────
    function renderRankingTable(employees) {
        const thead = document.getElementById('ranking-thead');
        const tbody = document.getElementById('ranking-tbody');
        if (!thead || !tbody) return;

        thead.innerHTML = `<tr>
            <th class="px-4 py-3 text-left">#</th>
            <th class="px-4 py-3 text-left">Employee</th>
            <th class="px-4 py-3 text-right">Qualified Revenue</th>
            <th class="px-4 py-3 text-right">Orders Closed</th>
            <th class="px-4 py-3 text-right">Avg Order Value</th>
            <th class="px-4 py-3 text-right">Paid Revenue</th>
            <th class="px-4 py-3 text-right">Account Revenue</th>
            <th class="px-4 py-3 text-right">POD Conv %</th>
            <th class="px-4 py-3 text-right">Revenue at Risk</th>
        </tr>`;

        tbody.innerHTML = '';
        employees.forEach((emp, i) => {
            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 text-gray-400 text-xs font-medium">${emp.rank || (i + 1)}</td>
                <td class="px-4 py-3 font-semibold text-gray-800 dark:text-white">
                    ${esc(emp.name)}
                    ${emp.employee_code ? `<span class="ml-1 text-xs text-gray-400 font-normal">${esc(emp.employee_code)}</span>` : ''}
                </td>
                <td class="px-4 py-3 text-right font-bold text-blue-600">${esc(emp.qualified_revenue)}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${esc(String(emp.orders_closed))}</td>
                <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">${esc(emp.avg_order_value)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">${esc(emp.paid_revenue)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-400">${esc(emp.account_revenue)}</td>
                <td class="px-4 py-3 text-right">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${emp.pod_rate >= 80 ? 'bg-green-100 text-green-700' : emp.pod_rate >= 50 ? 'bg-amber-100 text-amber-700' : 'bg-red-100 text-red-700'}">
                        ${emp.pod_rate}%
                    </span>
                </td>
                <td class="px-4 py-3 text-right text-red-500">${esc(emp.revenue_at_risk)}</td>`;
            tbody.appendChild(tr);
        });
    }

    // ── Initial render ────────────────────────────────────────────────────────
    // Deferred to DOMContentLoaded — window.ApexCharts is supplied by the
    // Vite module bundle, which the HTML spec guarantees executes before
    // DOMContentLoaded fires, while this inline script would otherwise run
    // at parse time and throw "ApexCharts is not defined". Same pattern as
    // every other Sales Report page.
    document.addEventListener('DOMContentLoaded', () => {
        if (reportData.employees?.length) {
            renderAll(reportData);
        }
    });

})();
</script>
@endpush

@push('css')
<style>
    #ranking-thead th { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; }
</style>
@endpush
