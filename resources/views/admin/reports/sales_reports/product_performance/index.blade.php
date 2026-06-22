@extends('admin.layouts.app')

@section('title', 'Product Performance')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports › Product Performance</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Product Performance</h3>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-lg px-3 py-2 max-w-sm">
            Includes paid orders and account orders regardless of payment collection.
            <span id="account-rev-note" class="font-medium text-amber-700 dark:text-amber-400"></span>
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

        <div class="flex flex-wrap items-end gap-3">

            {{-- Date Range --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                <select id="f-date-range"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value=""          @selected(($filters['date_range'] ?? '') === '')>All Time</option>
                    <option value="today"     @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="yesterday" @selected(($filters['date_range'] ?? '') === 'yesterday')>Yesterday</option>
                    <option value="last_7"    @selected(($filters['date_range'] ?? '') === 'last_7')>Last 7 Days</option>
                    <option value="last_30"   @selected(($filters['date_range'] ?? '') === 'last_30')>30 Day Rolling</option>
                    <option value="mtd"       @selected(($filters['date_range'] ?? 'mtd') === 'mtd')>Month to Date</option>
                    <option value="qtd"       @selected(($filters['date_range'] ?? '') === 'qtd')>Quarter to Date</option>
                    <option value="ytd"       @selected(($filters['date_range'] ?? '') === 'ytd')>Year to Date</option>
                    <option value="month"     @selected(($filters['date_range'] ?? '') === 'month')>Specific Month</option>
                    <option value="custom"    @selected(($filters['date_range'] ?? '') === 'custom')>Custom Range</option>
                </select>
            </div>

            {{-- Specific Month inputs --}}
            <div id="month-wrap" class="{{ ($filters['date_range'] ?? '') === 'month' ? '' : 'hidden' }} flex gap-2">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Month</label>
                    <select id="f-month"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach(['Jan'=>1,'Feb'=>2,'Mar'=>3,'Apr'=>4,'May'=>5,'Jun'=>6,'Jul'=>7,'Aug'=>8,'Sep'=>9,'Oct'=>10,'Nov'=>11,'Dec'=>12] as $label => $num)
                            <option value="{{ $num }}" @selected((int)($filters['month'] ?? now()->month) === $num)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Year</label>
                    <select id="f-year"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        @foreach(range(now()->year, 2024, -1) as $yr)
                            <option value="{{ $yr }}" @selected((int)($filters['year'] ?? now()->year) === $yr)>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <option value="all"    @selected(($filters['sale_type'] ?? 'all') === 'all')>All Types</option>
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

            {{-- Sort By --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sort By</label>
                <select id="f-sort-by"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="revenue">Revenue</option>
                    <option value="qty">Quantity</option>
                </select>
            </div>

            {{-- Quantity (limit) --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Quantity</label>
                <select id="f-limit"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all">All</option>
                    <option value="5">Top 5</option>
                    <option value="10" selected>Top 10</option>
                    <option value="15">Top 15</option>
                    <option value="20">Top 20</option>
                </select>
            </div>

            {{-- Run button --}}
            <div>
                <button type="button" id="btn-run"
                    class="rounded-md bg-blue-600 hover:bg-blue-700 px-4 py-2 text-sm font-medium text-white transition">
                    Run Report
                </button>
            </div>

        </div>
    </div>

    {{-- ── KPI CARDS ───────────────────────────────────────────────────────── --}}
    <div id="kpi-section" class="grid grid-cols-2 md:grid-cols-4 xl:grid-cols-4 gap-4 mb-6">

        {{-- Total Product Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Product Revenue</p>
            <p id="kpi-total-revenue" class="text-2xl font-bold text-gray-800 dark:text-white">—</p>
            <p class="text-xs text-gray-400 mt-1">Paid + Account demand</p>
        </div>

        {{-- Revenue Type Mix --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm col-span-2 md:col-span-1">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-2">Revenue Type Mix</p>
            <div class="flex items-center justify-between text-sm mb-1">
                <span class="text-gray-600 dark:text-gray-300">Paid</span>
                <span id="kpi-paid-rev" class="font-semibold text-gray-800 dark:text-white">—</span>
                <span id="kpi-paid-pct" class="text-xs text-blue-600 dark:text-blue-400 font-medium">—</span>
            </div>
            <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600 dark:text-gray-300">Account</span>
                <span id="kpi-acct-rev" class="font-semibold text-gray-800 dark:text-white">—</span>
                <span id="kpi-acct-pct" class="text-xs text-orange-600 dark:text-orange-400 font-medium">—</span>
            </div>
        </div>

        {{-- Total Quantity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Total Quantity</p>
            <p id="kpi-total-qty" class="text-2xl font-bold text-gray-800 dark:text-white">—</p>
            <p class="text-xs text-gray-400 mt-1">Units / rentals</p>
        </div>

        {{-- Avg Rev / Transaction --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Avg Rev / Transaction</p>
            <p id="kpi-avg-txn" class="text-2xl font-bold text-gray-800 dark:text-white">—</p>
            <p id="kpi-txn-count" class="text-xs text-gray-400 mt-1">— transactions</p>
        </div>

        {{-- Top Category --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Top Category by Revenue</p>
            <p id="kpi-top-category" class="text-base font-bold text-gray-800 dark:text-white truncate">—</p>
        </div>

        {{-- Top Product by Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Top Product by Revenue</p>
            <p id="kpi-top-prod-rev" class="text-base font-bold text-gray-800 dark:text-white truncate">—</p>
        </div>

        {{-- Top Product by Quantity --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
            <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">Top Product by Quantity</p>
            <p id="kpi-top-prod-qty" class="text-base font-bold text-gray-800 dark:text-white truncate">—</p>
        </div>

    </div>

    {{-- ── VIEW TABS ───────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm mb-6">

        {{-- Tab bar --}}
        <div class="border-b border-gray-200 dark:border-gray-700 px-4">
            <nav class="flex gap-1 -mb-px" id="view-tabs">
                <button data-tab="categories"
                    class="tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                    Top Categories
                </button>
                <button data-tab="products"
                    class="tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                    Top Products
                </button>
                <button data-tab="stores" id="tab-stores-btn"
                    class="tab-btn px-4 py-3 text-sm font-medium border-b-2 transition-colors">
                    Store Comparison
                </button>
            </nav>
        </div>

        {{-- Date range label + totals strip --}}
        <div class="flex items-center justify-between px-5 py-3 text-sm text-gray-500 dark:text-gray-400 border-b border-gray-100 dark:border-gray-700">
            <span id="date-range-label">{{ $dateRangeLabel }}</span>
            <span id="totals-strip"></span>
        </div>

        {{-- Chart --}}
        <div class="p-5">
            <h4 id="chart-title" class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3"></h4>
            <div id="perf-chart" style="min-height: 320px;"></div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead id="table-head" class="bg-gray-50 dark:bg-gray-700 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wide">
                </thead>
                <tbody id="table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                </tbody>
            </table>
        </div>

        {{-- Empty state --}}
        <div id="empty-state" class="hidden py-16 text-center text-gray-400 dark:text-gray-500">
            <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto mb-3 opacity-30" />
            <p class="text-sm">No data for the selected filters.</p>
        </div>

        {{-- Loading overlay --}}
        <div id="loading-overlay" class="hidden py-16 text-center text-gray-400">
            <svg class="animate-spin h-8 w-8 mx-auto text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
            </svg>
        </div>

    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE    = @json(route('admin.reports.sales-reports.product-performance.index'));
    const initData = @json($data);
    const initView = @json($view);

    const STORE_COLORS   = ['#3B82F6','#F97316','#10B981','#8B5CF6','#EF4444','#F59E0B','#06B6D4','#84CC16'];
    const SINGLE_COLOR   = '#3B82F6';

    const currFmt = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 });
    function fmt(v)    { return currFmt.format(v || 0); }
    function fmtFull(v){ return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0); }
    function fmtQty(v) { return Number(v || 0).toLocaleString('en-US'); }
    function fmtPct(v) { return (v || 0) + '%'; }

    function fmtLabel(val) {
        if (!val || val === 0) return '';
        return '$' + Math.round(Math.abs(val)).toLocaleString('en-US');
    }

    let chart        = null;
    let activeView   = initView || 'categories';
    let reportData   = null;
    let debounceTimer = null;

    // DOM refs
    const fDateRange   = document.getElementById('f-date-range');
    const fStartDate   = document.getElementById('f-start-date');
    const fEndDate     = document.getElementById('f-end-date');
    const customWrap   = document.getElementById('custom-date-wrap');
    const monthWrap    = document.getElementById('month-wrap');
    const fMonth       = document.getElementById('f-month');
    const fYear        = document.getElementById('f-year');
    const fStore       = document.getElementById('f-store');
    const fSaleType    = document.getElementById('f-sale-type');
    const fCategory    = document.getElementById('f-category');
    const fProduct     = document.getElementById('f-product');
    const fSortBy      = document.getElementById('f-sort-by');
    const fLimit       = document.getElementById('f-limit');
    const tabStoresBtn = document.getElementById('tab-stores-btn');

    // ── Tabs ────────────────────────────────────────────────────────────────
    function setActiveTab(tab) {
        activeView = tab;
        document.querySelectorAll('.tab-btn').forEach(btn => {
            const active = btn.dataset.tab === tab;
            btn.classList.toggle('border-blue-600', active);
            btn.classList.toggle('text-blue-600', active);
            btn.classList.toggle('dark:text-blue-400', active);
            btn.classList.toggle('border-transparent', !active);
            btn.classList.toggle('text-gray-500', !active);
            btn.classList.toggle('dark:text-gray-400', !active);
        });
    }

    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            if (this.dataset.tab === 'stores' && fStore.value !== 'all_individually') return;
            setActiveTab(this.dataset.tab);
            runReport();
        });
    });

    // ── Filter cascade: category → product ─────────────────────────────────
    fCategory.addEventListener('change', function () {
        fProduct.value = '';
        const catId = this.value;
        if (!catId) {
            while (fProduct.options.length > 1) fProduct.remove(1);
            scheduleRun();
            return;
        }
        fetch(ROUTE + '?ajax_products=1&category=' + catId)
            .then(r => r.json())
            .then(json => {
                while (fProduct.options.length > 1) fProduct.remove(1);
                (json.products || []).forEach(p => {
                    const opt = document.createElement('option');
                    opt.value = p.id;
                    opt.textContent = p.product_name;
                    fProduct.appendChild(opt);
                });
                scheduleRun();
            })
            .catch(() => scheduleRun());
    });

    // ── Store mode: show/hide Store Comparison tab ──────────────────────────
    fStore.addEventListener('change', function () {
        const isAllIndiv = this.value === 'all_individually';
        tabStoresBtn.classList.toggle('opacity-40', !isAllIndiv);
        tabStoresBtn.classList.toggle('cursor-not-allowed', !isAllIndiv);
        if (!isAllIndiv && activeView === 'stores') {
            setActiveTab('categories');
        }
        scheduleRun();
    });

    // ── Date range toggle ───────────────────────────────────────────────────
    fDateRange.addEventListener('change', function () {
        customWrap.classList.toggle('hidden', this.value !== 'custom');
        monthWrap.classList.toggle('hidden', this.value !== 'month');
        scheduleRun();
    });

    // ── Generic filter listeners ────────────────────────────────────────────
    [fStartDate, fEndDate, fMonth, fYear, fSaleType, fProduct, fSortBy, fLimit].forEach(el => {
        el.addEventListener('change', scheduleRun);
    });

    document.getElementById('btn-run').addEventListener('click', runReport);

    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fDateRange.value = 'mtd';
        customWrap.classList.add('hidden');
        monthWrap.classList.add('hidden');
        fStartDate.value = '';
        fEndDate.value   = '';
        fStore.value     = '';
        fSaleType.value  = 'all';
        fCategory.value  = '';
        fProduct.value   = '';
        fSortBy.value    = 'revenue';
        fLimit.value     = '10';
        tabStoresBtn.classList.add('opacity-40', 'cursor-not-allowed');
        if (activeView === 'stores') setActiveTab('categories');
        runReport();
    });

    function scheduleRun() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runReport, 400);
    }

    function collectParams() {
        const p = new URLSearchParams();

        // When a category or product is selected while on the Categories tab,
        // send view=products so the server runs productData() — which has always
        // been wired to that value in the original engine — rather than routing
        // through category_drilldown which requires newer server code.
        let sendView = activeView;
        if (activeView === 'categories' && (fCategory.value || fProduct.value)) {
            sendView = 'products';
        }
        p.set('view', sendView);

        p.set('date_range', fDateRange.value);
        if (fDateRange.value === 'custom') {
            if (fStartDate.value) p.set('start_date', fStartDate.value);
            if (fEndDate.value)   p.set('end_date',   fEndDate.value);
        }
        if (fDateRange.value === 'month') {
            if (fMonth.value) p.set('month', fMonth.value);
            if (fYear.value)  p.set('year',  fYear.value);
        }
        if (fStore.value)    p.set('store',     fStore.value);
        if (fSaleType.value && fSaleType.value !== 'all') p.set('sale_type', fSaleType.value);
        if (fCategory.value) p.set('category',  fCategory.value);
        if (fProduct.value)  p.set('product',   fProduct.value);
        p.set('limit', fLimit.value || '10');
        return p;
    }

    function runReport() {
        setLoading(true);
        fetch(ROUTE + '?' + collectParams().toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(r => r.json())
            .then(json => {
                if (!json.success) { console.error('Report error', json.error); return; }
                reportData = json.data;
                renderAll(json.data);
            })
            .finally(() => setLoading(false));
    }

    function setLoading(on) {
        document.getElementById('loading-overlay').classList.toggle('hidden', !on);
    }

    // ── Chart title ──────────────────────────────────────────────────────────
    function getChartTitle(data) {
        const v = data.view;
        if (v === 'stores') return 'Store Comparison by Revenue';

        // Product-level views: title depends on which filters are active
        if (v === 'products' || v === 'category_drilldown' || v === 'product_single') {
            // Single product selected
            if (fProduct.value) {
                const pSel = fProduct.options[fProduct.selectedIndex];
                return (pSel && pSel.value) ? pSel.text + ' Performance' : 'Product Performance';
            }
            // Category drilldown (category selected, all products within it)
            if (fCategory.value) {
                const cSel = fCategory.options[fCategory.selectedIndex];
                return (cSel && cSel.value)
                    ? 'Products in ' + cSel.text + ' by Revenue'
                    : 'Products by Revenue';
            }
            return 'Top Products by Revenue';
        }

        return 'Top Categories by Revenue';
    }

    function updateChartTitle(data) {
        const el = document.getElementById('chart-title');
        if (el) el.textContent = getChartTitle(data);
    }

    // ── Render ───────────────────────────────────────────────────────────────
    function renderAll(data) {
        renderKpis(data.kpis);
        renderChart(data);
        renderTable(data);
        renderDateLabel(data);
        renderTotalsStrip(data);
        updateChartTitle(data);
    }

    function renderKpis(k) {
        document.getElementById('kpi-total-revenue').textContent = fmtFull(k.total_revenue);
        document.getElementById('kpi-paid-rev').textContent      = fmt(k.paid_revenue);
        document.getElementById('kpi-paid-pct').textContent      = fmtPct(k.paid_pct);
        document.getElementById('kpi-acct-rev').textContent      = fmt(k.account_revenue);
        document.getElementById('kpi-acct-pct').textContent      = fmtPct(k.account_pct);
        document.getElementById('kpi-total-qty').textContent     = fmtQty(k.total_qty);
        document.getElementById('kpi-avg-txn').textContent       = fmt(k.avg_rev_per_txn);
        document.getElementById('kpi-txn-count').textContent     = fmtQty(k.txn_count) + ' transactions';
        document.getElementById('kpi-top-category').textContent  = k.top_category   || '—';
        document.getElementById('kpi-top-prod-rev').textContent  = k.top_product_by_rev || '—';
        document.getElementById('kpi-top-prod-qty').textContent  = k.top_product_by_qty || '—';

        const acctNote = document.getElementById('account-rev-note');
        if (k.account_revenue > 0) {
            acctNote.textContent = ' Account demand included: ' + fmt(k.account_revenue) + '.';
        } else {
            acctNote.textContent = '';
        }
    }

    function renderDateLabel(data) {
        // Server sends dateRangeLabel via controller; for AJAX we don't re-send it,
        // so keep whatever is already displayed.
    }

    function renderTotalsStrip(data) {
        const t = data.totals || {};
        document.getElementById('totals-strip').textContent =
            'Total: ' + fmtFull(t.revenue || 0) + ' | ' + fmtQty(t.qty || 0) + ' qty';
    }

    // ── Chart ────────────────────────────────────────────────────────────────
    function renderChart(data) {
        const chartData = data.chart || {};
        const labels    = chartData.labels  || [];
        const series    = chartData.series  || [];
        const isMulti   = series.length > 1;
        const colors    = isMulti ? STORE_COLORS : [SINGLE_COLOR];

        const options = {
            chart: {
                type:    'bar',
                height:  Math.max(280, labels.length * 32 + 80),
                toolbar: { show: false },
            },
            series:   series,
            xaxis: {
                categories: labels,
                labels: {
                    formatter: v => fmtLabel(v),
                },
            },
            plotOptions: {
                bar: {
                    horizontal:   true,
                    borderRadius: 3,
                    dataLabels:   { position: 'top' },
                },
            },
            dataLabels: {
                enabled:   true,
                formatter: fmtLabel,
                offsetX:   8,
                style:     { fontSize: '11px', fontWeight: '500', colors: ['#374151'] },
                background: { enabled: false },
            },
            colors: colors,
            tooltip: {
                y: { formatter: v => fmtFull(v) },
            },
            legend: { show: isMulti },
            grid:   { borderColor: '#e5e7eb' },
        };

        if (chart) {
            chart.destroy();
            chart = null;
        }

        chart = new ApexCharts(document.getElementById('perf-chart'), options);
        chart.render();
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    function renderTable(data) {
        const rows   = data.rows   || [];
        const stores = data.stores || [];
        const isMultiStore = stores.length > 0;
        const tbody  = document.getElementById('table-body');
        const thead  = document.getElementById('table-head');
        const empty  = document.getElementById('empty-state');
        const sortBy = fSortBy.value || 'revenue';

        tbody.innerHTML = '';
        thead.innerHTML = '';
        empty.classList.add('hidden');

        if (!rows.length) { empty.classList.remove('hidden'); return; }

        // Build sorted copy
        const sorted = [...rows].sort((a, b) =>
            sortBy === 'qty'
                ? (b.qty || b.total_qty || 0) - (a.qty || a.total_qty || 0)
                : (b.revenue || b.total_revenue || 0) - (a.revenue || a.total_revenue || 0)
        );

        const effectiveView = data.view || activeView;

        if (isMultiStore) {
            renderMultiStoreTable(thead, tbody, sorted, stores);
        } else if (effectiveView === 'products' || effectiveView === 'category_drilldown' || effectiveView === 'product_single') {
            renderProductsTable(thead, tbody, sorted);
        } else {
            renderCategoriesTable(thead, tbody, sorted);
        }
    }

    function renderCategoriesTable(thead, tbody, rows) {
        thead.innerHTML = `<tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3 text-right">Revenue</th>
            <th class="px-4 py-3 text-right">Qty</th>
            <th class="px-4 py-3 text-right">Avg Rev / Txn</th>
            <th class="px-4 py-3 text-right">% Rev</th>
            <th class="px-4 py-3 text-right">% Qty</th>
        </tr>`;
        rows.forEach((r, i) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 text-gray-400 text-xs">${i + 1}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">${esc(r.category_name || r.name || '—')}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-white">${fmtFull(r.revenue)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${fmtQty(r.qty)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${fmt(r.avg_rev_per_txn)}</td>
                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 text-xs font-medium">${fmtPct(r.revenue_pct)}</td>
                <td class="px-4 py-3 text-right text-gray-400 text-xs">${fmtPct(r.qty_pct)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderProductsTable(thead, tbody, rows) {
        thead.innerHTML = `<tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Product</th>
            <th class="px-4 py-3">Category</th>
            <th class="px-4 py-3">Type</th>
            <th class="px-4 py-3 text-right">Revenue</th>
            <th class="px-4 py-3 text-right">Qty</th>
            <th class="px-4 py-3 text-right">Avg Rev / Txn</th>
            <th class="px-4 py-3 text-right">% Rev</th>
            <th class="px-4 py-3 text-right">% Qty</th>
        </tr>`;
        rows.forEach((r, i) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 text-gray-400 text-xs">${i + 1}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">${esc(r.product_name || r.name || '—')}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.category_name || '—')}</td>
                <td class="px-4 py-3 text-gray-500 dark:text-gray-400 text-xs">${esc(r.product_type || '—')}</td>
                <td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-white">${fmtFull(r.revenue)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${fmtQty(r.qty)}</td>
                <td class="px-4 py-3 text-right text-gray-600 dark:text-gray-300">${fmt(r.avg_rev_per_txn)}</td>
                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 text-xs font-medium">${fmtPct(r.revenue_pct)}</td>
                <td class="px-4 py-3 text-right text-gray-400 text-xs">${fmtPct(r.qty_pct)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function renderMultiStoreTable(thead, tbody, rows, stores) {
        const storeCols = stores.map(s =>
            `<th class="px-4 py-3 text-right" colspan="2">${esc(s.name)}</th>`
        ).join('');
        const storeSubCols = stores.map(() =>
            `<th class="px-4 py-3 text-right text-gray-400">Rev</th><th class="px-4 py-3 text-right text-gray-400">Qty</th>`
        ).join('');
        thead.innerHTML = `<tr>
            <th class="px-4 py-3">#</th>
            <th class="px-4 py-3">Name</th>
            ${storeCols}
            <th class="px-4 py-3 text-right">Total Rev</th>
            <th class="px-4 py-3 text-right">% Rev</th>
        </tr><tr class="text-xs">
            <th class="px-4 py-2"></th>
            <th class="px-4 py-2"></th>
            ${storeSubCols}
            <th class="px-4 py-2"></th>
            <th class="px-4 py-2"></th>
        </tr>`;
        rows.forEach((r, i) => {
            const storeCells = stores.map(s => {
                const sd = (r.stores || {})[s.id] || { revenue: 0, qty: 0 };
                return `<td class="px-4 py-3 text-right font-semibold text-gray-800 dark:text-white">${fmtFull(sd.revenue)}</td>
                        <td class="px-4 py-3 text-right text-gray-500 dark:text-gray-400 text-xs">${fmtQty(sd.qty)}</td>`;
            }).join('');
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50 dark:hover:bg-gray-700/40 transition-colors';
            tr.innerHTML = `
                <td class="px-4 py-3 text-gray-400 text-xs">${i + 1}</td>
                <td class="px-4 py-3 font-medium text-gray-800 dark:text-white">${esc(r.name || '—')}</td>
                ${storeCells}
                <td class="px-4 py-3 text-right font-bold text-gray-800 dark:text-white">${fmtFull(r.total_revenue)}</td>
                <td class="px-4 py-3 text-right text-blue-600 dark:text-blue-400 text-xs font-medium">${fmtPct(r.revenue_pct)}</td>
            `;
            tbody.appendChild(tr);
        });
    }

    function esc(str) {
        return String(str ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        // Init tab state
        setActiveTab(activeView);

        // Init Store Comparison tab state
        const isAllIndiv = fStore.value === 'all_individually';
        tabStoresBtn.classList.toggle('opacity-40', !isAllIndiv);
        tabStoresBtn.classList.toggle('cursor-not-allowed', !isAllIndiv);

        // Render initial server-side data
        reportData = initData;
        renderAll(initData);
    });

})();
</script>
@endpush
