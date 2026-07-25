@extends('admin.layouts.app')

@section('title', 'Product Sales Ranking')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports › Product Performance</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Product Sales Ranking</h3>
        </div>
    </div>

    {{-- ── FILTERS ────────────────────────────────────────────────────────── --}}
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
                    <option value="custom"    @selected(($filters['date_range'] ?? '') === 'custom')>Custom Range</option>
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

            {{-- Sale Type --}}
            @php $saleType = $filters['sale_type'] ?? 'all'; @endphp
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sale Type</label>
                <div id="f-sale-type-group" class="inline-flex rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden text-sm">
                    @foreach (['all' => 'All Sales', 'rental' => 'Rental', 'retail' => 'Retail'] as $val => $label)
                    <button type="button" data-value="{{ $val }}"
                        class="sale-type-btn px-4 py-2 font-medium transition-colors
                            {{ $val !== 'all' ? 'border-l border-gray-300 dark:border-gray-600' : '' }}
                            {{ $saleType === $val ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        {{ $label }}
                    </button>
                    @endforeach
                </div>
                <input type="hidden" id="f-sale-type" value="{{ $saleType }}">
            </div>

            {{-- Payment Status --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Payment</label>
                <select id="f-payment-status"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    @include('admin.reports.partials._payment_status_options', ['filters' => $filters])
                </select>
            </div>

            {{-- Sort By --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Sort By</label>
                <div class="inline-flex rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden">
                    <button type="button" data-sort="revenue"
                        class="sort-btn px-3 py-2 text-sm font-medium border-r border-gray-300 dark:border-gray-600 transition
                            {{ ($filters['sort_by'] ?? 'revenue') === 'revenue' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        Highest Revenue
                    </button>
                    <button type="button" data-sort="qty"
                        class="sort-btn px-3 py-2 text-sm font-medium transition
                            {{ ($filters['sort_by'] ?? 'revenue') === 'qty' ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600' }}">
                        Highest Qty
                    </button>
                </div>
            </div>

        </div>

        {{-- Row 2: Checkboxes --}}
        @php
            $dmgVal  = $filters['damage_waiver']   ?? 'all';
            $trkVal  = $filters['track_insurance']  ?? 'all';
            $delVal  = $filters['delivery']         ?? 'all';
            $shpVal  = $filters['shipping']         ?? 'all';
        @endphp
        <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-3 pt-3 border-t border-gray-100 dark:border-gray-700 text-sm text-gray-700 dark:text-gray-300">

            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Include/Exclude:</span>

            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-dmg-exclude" class="rounded text-brand-500" {{ $dmgVal === 'exclude' ? 'checked' : '' }}>
                Excl. Damage Waiver
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-dmg-only" class="rounded text-brand-500" {{ $dmgVal === 'only' ? 'checked' : '' }}>
                DW Only
            </label>

            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-trk-exclude" class="rounded text-brand-500" {{ $trkVal === 'exclude' ? 'checked' : '' }}>
                Excl. Track Ins.
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-trk-only" class="rounded text-brand-500" {{ $trkVal === 'only' ? 'checked' : '' }}>
                Track Ins. Only
            </label>

            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-del-exclude" class="rounded text-brand-500" {{ $delVal === 'exclude' ? 'checked' : '' }}>
                Excl. Delivery
            </label>
            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-del-only" class="rounded text-brand-500" {{ $delVal === 'only' ? 'checked' : '' }}>
                Delivery Only
            </label>

            <label class="flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" id="cb-shp-exclude" class="rounded text-brand-500" {{ $shpVal === 'exclude' ? 'checked' : '' }}>
                Excl. Shipping
            </label>
        </div>
    </div>

    {{-- ── KPI CARDS ──────────────────────────────────────────────────────── --}}
    <div id="kpi-section" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 transition-opacity duration-200">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Products Ranked</div>
            <div id="kpi-count" class="text-2xl font-bold text-gray-900 dark:text-white">—</div>
            <div class="text-xs text-gray-400 mt-1">unique products with sales</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Total Revenue</div>
            <div id="kpi-revenue" class="text-2xl font-bold text-gray-900 dark:text-white">—</div>
            <div id="kpi-date-label" class="text-xs text-gray-400 mt-1">—</div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Total Units Sold</div>
            <div id="kpi-qty" class="text-2xl font-bold text-gray-900 dark:text-white">—</div>
            <div class="text-xs text-gray-400 mt-1">across all products</div>
        </div>

    </div>

    {{-- ── CHART (Top 15) ─────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 mb-5">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Top 15 Products</h2>
                <p id="chart-subtitle" class="text-xs text-gray-400 mt-0.5">by revenue</p>
            </div>
        </div>

        <div id="chart-no-data" class="hidden text-center py-14 text-gray-400 text-sm">
            Select a date range to view the ranking.
        </div>
        <div id="ranking-chart" style="min-height: 320px;"></div>

    </div>

    {{-- ── FULL RANKING TABLE ──────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Full Product Ranking</h2>
            <span id="table-count" class="text-xs text-gray-400"></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-center w-12">#</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Qty Sold</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Revenue</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Avg Price</th>
                        <th class="px-4 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap min-w-[130px]">Rev Share</th>
                    </tr>
                </thead>
                <tbody id="ranking-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">Loading…</td>
                    </tr>
                </tbody>
                <tfoot id="ranking-table-foot" class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 font-semibold text-gray-800 dark:text-white"></tfoot>
            </table>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE = '{{ route('admin.reports.sales-reports.product-performance.product-sales-ranking.index') }}';

    const initialData = @json($data);

    // ── State ─────────────────────────────────────────────────────────────────
    let reportData  = null;
    let activeSortBy = '{{ $filters['sort_by'] ?? 'revenue' }}';
    let rankChart   = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fDateRange  = document.getElementById('f-date-range');
    const fStartDate  = document.getElementById('f-start-date');
    const fEndDate    = document.getElementById('f-end-date');
    const fStore      = document.getElementById('f-store');
    const fItemType   = document.getElementById('f-item-type');
    const fCategory   = document.getElementById('f-category');
    const fProduct    = document.getElementById('f-product');
    const fSaleType   = document.getElementById('f-sale-type');
    const fSaleTypeGrp= document.getElementById('f-sale-type-group');
    const fPayment    = document.getElementById('f-payment-status');
    const cbDmgExclude= document.getElementById('cb-dmg-exclude');
    const cbDmgOnly   = document.getElementById('cb-dmg-only');
    const cbTrkExclude= document.getElementById('cb-trk-exclude');
    const cbTrkOnly   = document.getElementById('cb-trk-only');
    const cbDelExclude= document.getElementById('cb-del-exclude');
    const cbDelOnly   = document.getElementById('cb-del-only');
    const cbShpExclude= document.getElementById('cb-shp-exclude');
    const customWrap  = document.getElementById('custom-date-wrap');
    const kpiSection  = document.getElementById('kpi-section');

    // ── Formatters ────────────────────────────────────────────────────────────
    const currFmt = new Intl.NumberFormat('en-US', {
        style: 'currency', currency: window.APP_CURRENCY || 'USD',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    });
    const numFmt = new Intl.NumberFormat('en-US');

    function fmtCurr(v) { return currFmt.format(v || 0); }
    function fmtNum(v)  { return numFmt.format(v || 0); }

    // ── Sale Type toggle ──────────────────────────────────────────────────────
    function setSaleType(val) {
        fSaleType.value = val;
        fSaleTypeGrp.querySelectorAll('.sale-type-btn').forEach(b => {
            const on = b.dataset.value === val;
            b.className = 'sale-type-btn px-4 py-2 font-medium transition-colors ' +
                (b.dataset.value !== 'all' ? 'border-l border-gray-300 dark:border-gray-600 ' : '') +
                (on ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600');
        });
    }

    fSaleTypeGrp.addEventListener('click', function (e) {
        const btn = e.target.closest('.sale-type-btn');
        if (btn) { setSaleType(btn.dataset.value); scheduleRun(); }
    });

    // ── Checkbox mutex pairs ──────────────────────────────────────────────────
    function bindCheckboxPair(cbExcl, cbOnly) {
        cbExcl.addEventListener('change', function () {
            if (this.checked) cbOnly.checked = false;
            scheduleRun();
        });
        cbOnly.addEventListener('change', function () {
            if (this.checked) cbExcl.checked = false;
            scheduleRun();
        });
    }

    bindCheckboxPair(cbDmgExclude, cbDmgOnly);
    bindCheckboxPair(cbTrkExclude, cbTrkOnly);
    bindCheckboxPair(cbDelExclude, cbDelOnly);
    cbShpExclude.addEventListener('change', scheduleRun);

    // ── Category → Product AJAX ───────────────────────────────────────────────
    fCategory.addEventListener('change', function () {
        const catId = this.value;
        const url   = ROUTE + '?ajax_products=1' + (catId ? '&category=' + catId : '');
        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(resp => {
                fProduct.innerHTML = '<option value="">All Products</option>';
                (resp.products || []).forEach(p => {
                    const o = document.createElement('option');
                    o.value = p.id;
                    o.textContent = p.product_name;
                    fProduct.appendChild(o);
                });
            });
        scheduleRun();
    });

    // ── Collect filters ───────────────────────────────────────────────────────
    function collectFilters() {
        const p  = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) p.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate.value) p.set('start_date', fStartDate.value);
            if (fEndDate.value)   p.set('end_date',   fEndDate.value);
        }
        if (fStore.value)              p.set('store',        fStore.value);
        if (fItemType.value !== 'all') p.set('item_type',    fItemType.value);
        if (fCategory.value)           p.set('category',     fCategory.value);
        if (fProduct.value)            p.set('product',      fProduct.value);
        if (fSaleType.value && fSaleType.value !== 'all') p.set('sale_type', fSaleType.value);
        if (fPayment.value)            p.set('payment_status', fPayment.value);

        const dmg = cbDmgOnly.checked ? 'only' : cbDmgExclude.checked ? 'exclude' : 'all';
        const trk = cbTrkOnly.checked ? 'only' : cbTrkExclude.checked ? 'exclude' : 'all';
        const del = cbDelOnly.checked ? 'only' : cbDelExclude.checked ? 'exclude' : 'all';
        const shp = cbShpExclude.checked ? 'exclude' : 'all';
        if (dmg !== 'all') p.set('damage_waiver',   dmg);
        if (trk !== 'all') p.set('track_insurance', trk);
        if (del !== 'all') p.set('delivery',        del);
        if (shp !== 'all') p.set('shipping',        shp);

        p.set('sort_by', activeSortBy);
        return p;
    }

    // ── Fetch ─────────────────────────────────────────────────────────────────
    function runReport() {
        kpiSection.style.opacity = '0.4';

        fetch(ROUTE + '?' + collectFilters().toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
        .then(r => r.json())
        .then(resp => {
            if (!resp.success) throw new Error(resp.error || 'Report failed');
            reportData = resp.data;
            renderAll(reportData);
        })
        .catch(err => {
            console.error('Product Sales Ranking error:', err);
            alert('Report failed: ' + err.message);
        })
        .finally(() => { kpiSection.style.opacity = '1'; });
    }

    function renderAll(data) {
        renderKpis(data);
        renderChart(data);
        renderTable(data);
    }

    // ── KPI cards ─────────────────────────────────────────────────────────────
    function renderKpis(data) {
        const t = data.totals;
        document.getElementById('kpi-count').textContent   = fmtNum(t.product_count);
        document.getElementById('kpi-revenue').textContent = fmtCurr(t.revenue);
        document.getElementById('kpi-qty').textContent     = fmtNum(t.qty_sold);
        const lbl = document.getElementById('kpi-date-label');
        if (lbl) lbl.textContent = data.date_range_label || '';
    }

    // ── Chart ─────────────────────────────────────────────────────────────────
    function renderChart(data) {
        const noData  = document.getElementById('chart-no-data');
        const chartEl = document.getElementById('ranking-chart');
        const subtitle = document.getElementById('chart-subtitle');

        if (!data || !data.products || data.products.length === 0) {
            noData.classList.remove('hidden');
            chartEl.style.display = 'none';
            return;
        }
        noData.classList.add('hidden');
        chartEl.style.display = '';

        // Top 15 only in the chart
        const top = data.products.slice(0, 15);
        const isByQty = data.sort_by === 'qty';

        if (subtitle) subtitle.textContent = 'by ' + (isByQty ? 'units sold' : 'revenue');

        const labels = top.map(p => p.product_name.length > 30 ? p.product_name.substring(0, 28) + '…' : p.product_name);
        const values = top.map(p => isByQty ? p.qty_sold : p.revenue);

        const opts = {
            series: [{ name: isByQty ? 'Units Sold' : 'Revenue', data: values }],
            chart: {
                type: 'bar',
                height: Math.max(120, top.length * 60),
                toolbar: { show: false },
                animations: { enabled: true, speed: 350 },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    distributed: true,
                    barHeight: '50%',
                    dataLabels: { position: 'center' },
                },
            },
            dataLabels: {
                enabled: true,
                formatter: v => isByQty ? fmtNum(v) : fmtCurr(v),
                offsetX: 0,
                style: { fontSize: '14px', fontWeight: '700', colors: ['#ffffff'] },
            },
            colors: [
                '#3B82F6','#6366F1','#8B5CF6','#EC4899','#F43F5E',
                '#F97316','#EAB308','#22C55E','#14B8A6','#06B6D4',
                '#0EA5E9','#64748B','#84CC16','#A855F7','#F59E0B',
            ],
            legend: { show: false },
            states: {
                hover:  { filter: { type: 'darken', value: 0.15 } },
                active: { filter: { type: 'darken', value: 0.2  } },
            },
            xaxis: {
                categories: labels,
                labels: {
                    formatter: v => isByQty ? fmtNum(v) : fmtCurr(v),
                    style: { fontSize: '11px' },
                },
            },
            yaxis: { labels: { style: { fontSize: '11px' } } },
            tooltip: {
                theme: 'light',
                y: { formatter: v => isByQty ? fmtNum(v) : fmtCurr(v) },
            },
            grid: { borderColor: '#f3f4f6', xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        };

        if (!rankChart) {
            rankChart = new ApexCharts(chartEl, opts);
            rankChart.render();
        } else {
            rankChart.destroy();
            rankChart = new ApexCharts(chartEl, opts);
            rankChart.render();
        }
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    const TYPE_COLORS = {
        Rental:  'bg-blue-100 text-blue-700',
        Retail:  'bg-emerald-100 text-emerald-700',
        Service: 'bg-purple-100 text-purple-700',
        Fee:     'bg-orange-100 text-orange-700',
        Other:   'bg-gray-100 text-gray-600',
    };

    function typeBadge(type) {
        const cls = TYPE_COLORS[type] || 'bg-gray-100 text-gray-600';
        return `<span class="inline-block px-2 py-0.5 rounded-full text-xs font-medium ${cls}">${type}</span>`;
    }

    function shareBar(pct) {
        return `<div class="flex items-center gap-2">
            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 min-w-[60px]">
                <div class="bg-brand-500 h-1.5 rounded-full" style="width:${Math.min(pct, 100)}%"></div>
            </div>
            <span class="text-xs text-gray-500 tabular-nums w-10 text-right">${pct}%</span>
        </div>`;
    }

    function rankBadge(rank) {
        if (rank === 1) return `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-yellow-400 text-white text-xs font-bold">1</span>`;
        if (rank === 2) return `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-gray-400 text-white text-xs font-bold">2</span>`;
        if (rank === 3) return `<span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-amber-600 text-white text-xs font-bold">3</span>`;
        return `<span class="text-gray-400 text-sm tabular-nums">${rank}</span>`;
    }

    function renderTable(data) {
        const tbody = document.getElementById('ranking-table-body');
        const tfoot = document.getElementById('ranking-table-foot');
        const countEl = document.getElementById('table-count');

        if (!data || !data.products || data.products.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-gray-400 text-sm">No data for the selected period.</td></tr>';
            tfoot.innerHTML = '';
            if (countEl) countEl.textContent = '';
            return;
        }

        if (countEl) countEl.textContent = data.products.length + ' products';

        let rows = '';
        data.products.forEach(p => {
            rows += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                <td class="px-4 py-3 text-center">${rankBadge(p.rank)}</td>
                <td class="px-4 py-3 text-gray-800 dark:text-gray-200 font-medium max-w-xs">
                    <span title="${p.product_name}">${p.product_name}</span>
                </td>
                <td class="px-4 py-3">${typeBadge(p.product_type)}</td>
                <td class="px-4 py-3 text-right text-gray-900 dark:text-white tabular-nums">${fmtNum(p.qty_sold)}</td>
                <td class="px-4 py-3 text-right text-gray-900 dark:text-white tabular-nums whitespace-nowrap">${fmtCurr(p.revenue)}</td>
                <td class="px-4 py-3 text-right text-gray-500 tabular-nums whitespace-nowrap">${fmtCurr(p.avg_price)}</td>
                <td class="px-4 py-3">${shareBar(p.revenue_share)}</td>
            </tr>`;
        });
        tbody.innerHTML = rows;

        const t = data.totals;
        tfoot.innerHTML = `<tr>
            <td class="px-4 py-3 text-center text-xs text-gray-500">${fmtNum(t.product_count)}</td>
            <td class="px-4 py-3">All Products</td>
            <td class="px-4 py-3"></td>
            <td class="px-4 py-3 text-right tabular-nums">${fmtNum(t.qty_sold)}</td>
            <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">${fmtCurr(t.revenue)}</td>
            <td class="px-4 py-3 text-right tabular-nums whitespace-nowrap">${fmtCurr(t.avg_price)}</td>
            <td class="px-4 py-3"><span class="text-xs text-gray-500">100%</span></td>
        </tr>`;
    }

    // ── Sort By buttons ───────────────────────────────────────────────────────
    function syncSortBtns() {
        document.querySelectorAll('.sort-btn').forEach(b => {
            const on = b.dataset.sort === activeSortBy;
            b.className = 'sort-btn px-3 py-2 text-sm font-medium border-r border-gray-300 dark:border-gray-600 last:border-r-0 transition ' +
                (on ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-600');
        });
    }

    document.querySelectorAll('.sort-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            activeSortBy = this.dataset.sort;
            syncSortBtns();
            runReport();
        });
    });

    // ── Debounce & events ─────────────────────────────────────────────────────
    let debounceTimer = null;
    function scheduleRun() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(runReport, 400);
    }

    fDateRange.addEventListener('change', function () {
        customWrap.classList.toggle('hidden', this.value !== 'custom');
        scheduleRun();
    });

    [fStore, fItemType, fProduct, fPayment].forEach(el => {
        if (el) el.addEventListener('change', scheduleRun);
    });

    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fDateRange.value  = 'mtd';
        fStartDate.value  = '';
        fEndDate.value    = '';
        fStore.value      = '';
        fItemType.value   = 'all';
        fCategory.value   = '';
        fProduct.innerHTML = '<option value="">All Products</option>';
        setSaleType('all');
        fPayment.value    = 'paid';
        activeSortBy      = 'revenue';
        [cbDmgExclude, cbDmgOnly, cbTrkExclude, cbTrkOnly, cbDelExclude, cbDelOnly, cbShpExclude].forEach(cb => { cb.checked = false; });
        customWrap.classList.add('hidden');
        syncSortBtns();
        runReport();
    });

    // ── Init ──────────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        reportData = initialData;
        renderAll(initialData);
    });

})();
</script>
@endpush
