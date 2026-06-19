@extends('admin.layouts.app')

@section('title', 'Sales By Stores')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Sales By Stores</h3>
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

            {{-- Payment Status --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Payment</label>
                <select id="f-payment-status"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="paid"    @selected(($filters['payment_status'] ?? 'paid') === 'paid')>Paid</option>
                    <option value="all"     @selected(($filters['payment_status'] ?? '') === 'all')>All</option>
                    <option value="pod"     @selected(($filters['payment_status'] ?? '') === 'pod')>POD</option>
                    <option value="account" @selected(($filters['payment_status'] ?? '') === 'account')>Account</option>
                </select>
            </div>

        </div>
    </div>

    {{-- ── KPI CARDS ──────────────────────────────────────────────────────── --}}
    <div id="kpi-section" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6 transition-opacity duration-200">

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Total Revenue</div>
            <div id="kpi-revenue" class="text-2xl font-bold text-gray-900 dark:text-white mb-2">—</div>
            <div class="flex items-center gap-2">
                <span id="kpi-revenue-badge" class="inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">—</span>
                <span id="kpi-revenue-prev" class="text-xs text-gray-400"></span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Total Transactions</div>
            <div id="kpi-transactions" class="text-2xl font-bold text-gray-900 dark:text-white mb-2">—</div>
            <div class="flex items-center gap-2">
                <span id="kpi-tx-badge" class="inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">—</span>
                <span id="kpi-tx-prev" class="text-xs text-gray-400"></span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 shadow-sm">
            <div class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase tracking-wide mb-2">Avg Ticket</div>
            <div id="kpi-avg-ticket" class="text-2xl font-bold text-gray-900 dark:text-white mb-2">—</div>
            <div class="flex items-center gap-2">
                <span id="kpi-avg-badge" class="inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-full bg-gray-100 text-gray-500">—</span>
                <span id="kpi-avg-prev" class="text-xs text-gray-400"></span>
            </div>
        </div>

    </div>

    {{-- ── CHART ──────────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-5 mb-6 shadow-sm">

        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-5">
            <div>
                <h2 class="text-base font-semibold text-gray-900 dark:text-white">Store Comparison</h2>
                <p id="chart-date-label" class="text-xs text-gray-400 mt-0.5">—</p>
            </div>
            <div class="inline-flex rounded-md border border-gray-300 dark:border-gray-600 overflow-hidden">
                <button type="button" data-metric="revenue"
                    class="metric-btn px-3 py-1.5 text-sm font-medium border-r border-gray-300 dark:border-gray-600 bg-brand-500 text-white transition">
                    Revenue
                </button>
                <button type="button" data-metric="transactions"
                    class="metric-btn px-3 py-1.5 text-sm font-medium border-r border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition">
                    Transactions
                </button>
                <button type="button" data-metric="avg_ticket"
                    class="metric-btn px-3 py-1.5 text-sm font-medium bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50 transition">
                    Avg Ticket
                </button>
            </div>
        </div>

        <div id="chart-no-data" class="hidden text-center py-14 text-gray-400 text-sm">
            Select a date range to view store data.
        </div>
        <div id="store-chart" style="min-height: 300px;"></div>

    </div>

    {{-- ── STORE TABLE ────────────────────────────────────────────────────── --}}
    <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mb-6">
        <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 class="text-base font-semibold text-gray-900 dark:text-white">Store Breakdown</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-700/50">
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide whitespace-nowrap">Store</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Revenue</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Share</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">vs Prev</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Transactions</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">vs Prev</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">Avg Ticket</th>
                        <th class="px-5 py-3 text-xs font-semibold text-gray-500 uppercase tracking-wide text-right whitespace-nowrap">vs Prev</th>
                    </tr>
                </thead>
                <tbody id="store-table-body" class="divide-y divide-gray-100 dark:divide-gray-700">
                    <tr>
                        <td colspan="8" class="px-5 py-8 text-center text-gray-400 text-sm">Loading…</td>
                    </tr>
                </tbody>
                <tfoot id="store-table-foot" class="border-t-2 border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700/50 font-semibold text-gray-800 dark:text-white"></tfoot>
            </table>
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE = '{{ route('admin.reports.sales-reports.sales-by-stores.index') }}';

    const initialData = @json($data);

    // ── State ─────────────────────────────────────────────────────────────────
    let reportData   = null;
    let activeMetric = 'revenue';
    let storeChart   = null;

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fDateRange = document.getElementById('f-date-range');
    const fStartDate = document.getElementById('f-start-date');
    const fEndDate   = document.getElementById('f-end-date');
    const fStore     = document.getElementById('f-store');
    const fPayment   = document.getElementById('f-payment-status');
    const customWrap = document.getElementById('custom-date-wrap');
    const kpiSection = document.getElementById('kpi-section');

    // ── Formatters ────────────────────────────────────────────────────────────
    const currFmt = new Intl.NumberFormat('en-US', {
        style: 'currency', currency: window.APP_CURRENCY || 'USD',
        minimumFractionDigits: 0, maximumFractionDigits: 0,
    });
    const numFmt = new Intl.NumberFormat('en-US');

    function fmtCurr(v) { return currFmt.format(v || 0); }
    function fmtNum(v)  { return numFmt.format(v || 0); }
    function fmtVal(v, metric) {
        return metric === 'transactions' ? fmtNum(v) : fmtCurr(v);
    }

    // ── Filters ───────────────────────────────────────────────────────────────
    function collectFilters() {
        const p  = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) p.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate.value) p.set('start_date', fStartDate.value);
            if (fEndDate.value)   p.set('end_date',   fEndDate.value);
        }
        if (fStore.value)   p.set('store', fStore.value);
        if (fPayment.value) p.set('payment_status', fPayment.value);
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
            console.error('Sales By Stores error:', err);
            alert('Report failed: ' + err.message);
        })
        .finally(() => { kpiSection.style.opacity = '1'; });
    }

    function renderAll(data) {
        renderKpis(data);
        renderChart(data, activeMetric);
        renderTable(data);
        const lbl = document.getElementById('chart-date-label');
        if (lbl) lbl.textContent = data.date_range_label || '';
    }

    // ── KPI cards ─────────────────────────────────────────────────────────────
    function growthBadge(pct) {
        if (!pct) return { html: '<span>—</span>', cls: 'bg-gray-100 text-gray-500' };
        const up   = pct > 0;
        const icon = up
            ? '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7 7 7"/></svg>'
            : '<svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7-7-7"/></svg>';
        return {
            html: icon + '<span>' + Math.abs(pct).toFixed(1) + '%</span>',
            cls:  up ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-600',
        };
    }

    function setBadge(id, pct) {
        const el = document.getElementById(id);
        if (!el) return;
        const b = growthBadge(pct);
        el.innerHTML = b.html;
        el.className = 'inline-flex items-center gap-0.5 text-xs font-semibold px-1.5 py-0.5 rounded-full ' + b.cls;
    }

    function renderKpis(data) {
        const t = data.totals;
        document.getElementById('kpi-revenue').textContent      = fmtCurr(t.revenue);
        document.getElementById('kpi-transactions').textContent  = fmtNum(t.transactions);
        document.getElementById('kpi-avg-ticket').textContent   = fmtCurr(t.avg_ticket);

        setBadge('kpi-revenue-badge', t.revenue_growth);
        setBadge('kpi-tx-badge',      t.tx_growth);
        setBadge('kpi-avg-badge',     t.avg_ticket_growth);

        document.getElementById('kpi-revenue-prev').textContent = 'vs ' + fmtCurr(t.prev_revenue);
        document.getElementById('kpi-tx-prev').textContent      = 'vs ' + fmtNum(t.prev_transactions);
        document.getElementById('kpi-avg-prev').textContent     = 'vs ' + fmtCurr(t.prev_avg_ticket);
    }

    // ── Chart ─────────────────────────────────────────────────────────────────
    function buildChartData(data, metric) {
        const stores = data.stores;
        const labels = stores.map(s => s.name);

        const metricMap = {
            revenue:      { curr: 'revenue',      prev: 'prev_revenue' },
            transactions: { curr: 'transactions',  prev: 'prev_transactions' },
            avg_ticket:   { curr: 'avg_ticket',    prev: 'prev_avg_ticket' },
        };
        const keys = metricMap[metric] || metricMap.revenue;

        return {
            labels,
            current:  stores.map(s => s[keys.curr]),
            previous: stores.map(s => s[keys.prev]),
        };
    }

    function yFmt(v, metric) {
        return metric === 'transactions' ? fmtNum(v) : fmtCurr(v);
    }

    function chartOptions(data, metric) {
        const cd = buildChartData(data, metric);
        return {
            series: [
                { name: 'Current Period',  data: cd.current  },
                { name: 'Previous Period', data: cd.previous },
            ],
            chart: {
                type: 'bar',
                height: Math.max(240, cd.labels.length * 52),
                toolbar: { show: false },
                animations: { enabled: true, speed: 350 },
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    dataLabels: { position: 'top' },
                    barHeight: '60%',
                },
            },
            dataLabels: { enabled: false },
            colors: ['#3B82F6', '#64748B'],
            states: {
                hover:  { filter: { type: 'darken', value: 0.15 } },
                active: { filter: { type: 'darken', value: 0.2  } },
            },
            xaxis: {
                categories: cd.labels,
                labels: { formatter: v => yFmt(v, metric) },
            },
            yaxis: { labels: { style: { fontSize: '12px' } } },
            tooltip: {
                shared: true,
                intersect: false,
                y: { formatter: v => yFmt(v, metric) },
                theme: 'light',
            },
            legend: { position: 'top', horizontalAlign: 'right' },
            grid:   { borderColor: '#f3f4f6', xaxis: { lines: { show: true } }, yaxis: { lines: { show: false } } },
        };
    }

    function renderChart(data, metric) {
        const noData  = document.getElementById('chart-no-data');
        const chartEl = document.getElementById('store-chart');

        if (!data || !data.stores || data.stores.length === 0) {
            noData.classList.remove('hidden');
            chartEl.style.display = 'none';
            return;
        }
        noData.classList.add('hidden');
        chartEl.style.display = '';

        const cd = buildChartData(data, metric);

        if (!storeChart) {
            storeChart = new ApexCharts(chartEl, chartOptions(data, metric));
            storeChart.render();
        } else {
            storeChart.updateOptions({
                chart:   { height: Math.max(240, cd.labels.length * 52) },
                xaxis:   { categories: cd.labels, labels: { formatter: v => yFmt(v, metric) } },
                tooltip: { y: { formatter: v => yFmt(v, metric) }, theme: 'light' },
            }, false, false);
            storeChart.updateSeries([
                { name: 'Current Period',  data: cd.current  },
                { name: 'Previous Period', data: cd.previous },
            ]);
        }
    }

    // ── Table ─────────────────────────────────────────────────────────────────
    function deltaHtml(curr, prev) {
        if (prev === 0) return '<span class="text-gray-400">—</span>';
        const pct = (((curr - prev) / prev) * 100).toFixed(1);
        if (curr > prev) return `<span class="text-emerald-600 text-xs font-medium">↑ ${pct}%</span>`;
        if (curr < prev) return `<span class="text-red-500 text-xs font-medium">↓ ${Math.abs(pct)}%</span>`;
        return '<span class="text-gray-400 text-xs">—</span>';
    }

    function shareBar(pct) {
        return `<div class="flex items-center gap-2">
            <div class="flex-1 bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 min-w-[48px]">
                <div class="bg-brand-500 h-1.5 rounded-full" style="width:${Math.min(pct, 100)}%"></div>
            </div>
            <span class="text-xs text-gray-500 tabular-nums w-9 text-right">${pct}%</span>
        </div>`;
    }

    function renderTable(data) {
        const tbody = document.getElementById('store-table-body');
        const tfoot = document.getElementById('store-table-foot');

        if (!data || !data.stores || data.stores.length === 0) {
            tbody.innerHTML = '<tr><td colspan="8" class="px-5 py-8 text-center text-gray-400 text-sm">No data for the selected period.</td></tr>';
            tfoot.innerHTML = '';
            return;
        }

        let rows = '';
        data.stores.forEach(s => {
            rows += `<tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition">
                <td class="px-5 py-3 text-gray-800 dark:text-gray-200 font-medium whitespace-nowrap">${s.name}</td>
                <td class="px-5 py-3 text-right text-gray-900 dark:text-white whitespace-nowrap">${fmtCurr(s.revenue)}</td>
                <td class="px-5 py-3 min-w-[120px]">${shareBar(s.revenue_share)}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(s.revenue, s.prev_revenue)}</td>
                <td class="px-5 py-3 text-right text-gray-900 dark:text-white">${fmtNum(s.transactions)}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(s.transactions, s.prev_transactions)}</td>
                <td class="px-5 py-3 text-right text-gray-900 dark:text-white whitespace-nowrap">${fmtCurr(s.avg_ticket)}</td>
                <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(s.avg_ticket, s.prev_avg_ticket)}</td>
            </tr>`;
        });
        tbody.innerHTML = rows;

        const t = data.totals;
        tfoot.innerHTML = `<tr>
            <td class="px-5 py-3">All Stores</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">${fmtCurr(t.revenue)}</td>
            <td class="px-5 py-3"><span class="text-xs text-gray-500">100%</span></td>
            <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(t.revenue, t.prev_revenue)}</td>
            <td class="px-5 py-3 text-right">${fmtNum(t.transactions)}</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(t.transactions, t.prev_transactions)}</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">${fmtCurr(t.avg_ticket)}</td>
            <td class="px-5 py-3 text-right whitespace-nowrap">${deltaHtml(t.avg_ticket, t.prev_avg_ticket)}</td>
        </tr>`;
    }

    // ── Metric toggle ─────────────────────────────────────────────────────────
    function syncMetricBtns() {
        document.querySelectorAll('.metric-btn').forEach(b => {
            const on = b.dataset.metric === activeMetric;
            b.className = 'metric-btn px-3 py-1.5 text-sm font-medium border-r border-gray-300 dark:border-gray-600 last:border-r-0 transition ' +
                (on ? 'bg-brand-500 text-white' : 'bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-200 hover:bg-gray-50');
        });
    }

    document.querySelectorAll('.metric-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            activeMetric = this.dataset.metric;
            syncMetricBtns();
            if (reportData) renderChart(reportData, activeMetric);
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

    [fStore, fPayment].forEach(el => { if (el) el.addEventListener('change', scheduleRun); });

    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fDateRange.value = 'mtd';
        fStartDate.value = '';
        fEndDate.value   = '';
        fStore.value     = '';
        fPayment.value   = 'paid';
        customWrap.classList.add('hidden');
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
