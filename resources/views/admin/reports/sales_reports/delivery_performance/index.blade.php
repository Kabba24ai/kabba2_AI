@extends('admin.layouts.app')

@section('title', 'Delivery Performance')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Page Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Reports › Sales Reports › Delivery Performance</p>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Delivery Performance</h3>
        </div>
        <div class="text-xs text-gray-500 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg px-3 py-2 max-w-sm">
            Driver Performance is built from delivery/return checklist completion and question-answer results.
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
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(($filters['store'] ?? '') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>

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
    <div id="kpi-section" class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6"></div>

    {{-- ── CHARTS ───────────────────────────────────────────────────────────── --}}
    <div id="charts-section">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Driver Leaderboard — Completed Deliveries &amp; Returns</h4>
                <div id="chart-drivers" style="min-height:280px;"></div>
            </div>

            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Delivery / Return Step Funnel</h4>
                <div id="chart-funnel" style="min-height:280px;"></div>
            </div>
        </div>

        <div id="chart-flagged-wrap" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 mb-6 shadow-sm hidden">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">Top Flagged Checklist Questions</h4>
            <div id="chart-flagged" style="min-height:280px;"></div>
        </div>

    </div>

    {{-- ── EMPTY STATE ─────────────────────────────────────────────────────── --}}
    <div id="empty-state" class="hidden text-center py-16 bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm">
        <x-heroicon-o-truck class="w-12 h-12 mx-auto mb-3 text-gray-300 dark:text-gray-600" />
        <p class="text-gray-500 dark:text-gray-400 text-sm">No driver activity found for this period.</p>
        <p class="text-gray-400 dark:text-gray-500 text-xs mt-1">Try a different date range or store.</p>
    </div>

    {{-- ── DRIVER TABLE ─────────────────────────────────────────────────────── --}}
    <div id="table-section" class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm overflow-hidden mt-6">
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h4 class="text-sm font-semibold text-gray-700 dark:text-gray-300">Driver Performance Overview</h4>
            <span class="text-xs text-gray-400">Sorted by Total Completed</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 dark:bg-gray-700/50 text-xs text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-2 text-left">Driver</th>
                        <th class="px-4 py-2 text-right">Deliveries</th>
                        <th class="px-4 py-2 text-right">Returns</th>
                        <th class="px-4 py-2 text-right">Pending Del.</th>
                        <th class="px-4 py-2 text-right">Pending Ret.</th>
                        <th class="px-4 py-2 text-right">Checklist Pass %</th>
                        <th class="px-4 py-2 text-right">Avg Delivery Time</th>
                        <th class="px-4 py-2 text-right">Avg Return Time</th>
                    </tr>
                </thead>
                <tbody id="driver-tbody"></tbody>
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

    let reportData = @json($data);

    const fDateRange = document.getElementById('f-date-range');
    const fStartDate  = document.getElementById('f-start-date');
    const fEndDate    = document.getElementById('f-end-date');
    const fStore      = document.getElementById('f-store');
    const btnRun      = document.getElementById('btn-run');
    const btnClear    = document.getElementById('btn-clear-filters');
    const overlay     = document.getElementById('loading-overlay');

    const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#06b6d4'];
    const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    const pct = v => v === null || v === undefined ? '—' : v + '%';
    const mins = v => v === null || v === undefined ? '—' : (v >= 60 ? (v / 60).toFixed(1) + ' hrs' : Math.round(v) + ' min');

    let charts = {};

    fDateRange.addEventListener('change', () => {
        document.getElementById('custom-date-wrap').classList.toggle('hidden', fDateRange.value !== 'custom');
    });

    function buildParams() {
        const p = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) p.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate?.value) p.set('start_date', fStartDate.value);
            if (fEndDate?.value)   p.set('end_date',   fEndDate.value);
        }
        if (fStore.value) p.set('store', fStore.value);
        return p;
    }

    function runReport() {
        const params = buildParams();
        overlay.classList.remove('hidden');

        fetch(`{{ route('admin.reports.sales-reports.delivery-performance.index') }}?${params.toString()}`, {
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
    [fDateRange, fStore].forEach(el => el.addEventListener('change', runReport));

    btnClear.addEventListener('click', () => {
        fDateRange.value = 'mtd';
        if (fStartDate) fStartDate.value = '';
        if (fEndDate)   fEndDate.value   = '';
        fStore.value = '';
        document.getElementById('custom-date-wrap').classList.add('hidden');
        runReport();
    });

    function renderAll(data) {
        const drivers  = data.drivers || [];
        const hasData  = drivers.length > 0;

        document.getElementById('kpi-section').innerHTML = buildKpiCards(data.kpis || {});
        document.getElementById('charts-section').classList.toggle('hidden', !hasData);
        document.getElementById('empty-state').classList.toggle('hidden', hasData);
        document.getElementById('table-section').classList.toggle('hidden', !hasData);

        if (!hasData) return;

        renderDriverChart(drivers);
        renderFunnelChart(data.funnel || {});
        renderFlaggedChart(data.flagged || []);
        renderDriverTable(drivers);
    }

    function buildKpiCards(kpis) {
        const cards = [
            { label: 'Total Deliveries', value: (kpis.total_deliveries ?? 0).toLocaleString('en-US') },
            { label: 'Total Returns', value: (kpis.total_returns ?? 0).toLocaleString('en-US') },
            { label: 'Avg Checklist Pass Rate', value: pct(kpis.avg_checklist_pass) },
        ];
        return cards.map(c => `
            <div class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 p-4 shadow-sm">
                <p class="text-xs text-gray-500 dark:text-gray-400 mb-1">${esc(c.label)}</p>
                <p class="text-2xl font-semibold text-gray-800 dark:text-white/90">${esc(c.value)}</p>
            </div>
        `).join('');
    }

    function renderDriverChart(drivers) {
        if (charts['chart-drivers']) charts['chart-drivers'].destroy();

        charts['chart-drivers'] = new ApexCharts(document.getElementById('chart-drivers'), {
            chart: { type: 'bar', height: 280, stacked: true, toolbar: { show: false } },
            series: [
                { name: 'Deliveries', data: drivers.map(d => d.deliveries_completed) },
                { name: 'Returns',    data: drivers.map(d => d.returns_completed) },
            ],
            xaxis: { categories: drivers.map(d => d.name), labels: { style: { fontSize: '12px' } } },
            yaxis: { labels: { formatter: v => Math.round(v).toLocaleString('en-US') } },
            plotOptions: { bar: { horizontal: false, borderRadius: 4, columnWidth: '55%' } },
            colors: COLORS,
            legend: { show: true, position: 'top' },
            grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts['chart-drivers'].render();
    }

    function renderFunnelChart(funnel) {
        if (charts['chart-funnel']) charts['chart-funnel'].destroy();

        const total = funnel.total || 0;
        const steps = [
            { label: 'Terms Signed',       value: funnel.delivery_terms },
            { label: 'License Uploaded',   value: funnel.delivery_license },
            { label: 'Delivery Checklist', value: funnel.delivery_checklist },
            { label: 'Delivery Video',     value: funnel.delivery_video },
            { label: 'Return Checklist',   value: funnel.return_checklist },
            { label: 'Return Video',       value: funnel.return_video },
        ];

        charts['chart-funnel'] = new ApexCharts(document.getElementById('chart-funnel'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: [{ name: 'Orders Completed', data: steps.map(s => s.value || 0) }],
            xaxis: {
                categories: steps.map(s => s.label),
                labels: { formatter: v => Math.round(v).toLocaleString('en-US') },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '55%' } },
            colors: [COLORS[0]],
            dataLabels: {
                enabled: true,
                formatter: v => total ? Math.round(v / total * 100) + '%' : '0%',
            },
            tooltip: { y: { formatter: v => Math.round(v).toLocaleString('en-US') + ` of ${total.toLocaleString('en-US')} orders` } },
            grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts['chart-funnel'].render();
    }

    function renderFlaggedChart(flagged) {
        if (charts['chart-flagged']) charts['chart-flagged'].destroy();

        const wrap = document.getElementById('chart-flagged-wrap');

        if (!flagged.length) {
            wrap.classList.add('hidden');
            return;
        }

        wrap.classList.remove('hidden');
        charts['chart-flagged'] = new ApexCharts(document.getElementById('chart-flagged'), {
            chart: { type: 'bar', height: 280, toolbar: { show: false } },
            series: [{ name: 'Flagged Answers', data: flagged.map(f => f.flagged_count) }],
            xaxis: {
                categories: flagged.map(f => `${f.question} (${f.category})`),
                labels: { formatter: v => Math.round(v).toLocaleString('en-US') },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 4, barHeight: '60%' } },
            colors: ['#ef4444'],
            dataLabels: { enabled: true },
            grid: { borderColor: '#e5e7eb', strokeDashArray: 4 },
        });
        charts['chart-flagged'].render();
    }

    function renderDriverTable(drivers) {
        document.getElementById('driver-tbody').innerHTML = drivers.map(d => `
            <tr class="border-t border-gray-100 dark:border-gray-700">
                <td class="px-4 py-2 font-medium text-gray-700 dark:text-gray-200">${esc(d.name)}</td>
                <td class="px-4 py-2 text-right">${d.deliveries_completed}</td>
                <td class="px-4 py-2 text-right">${d.returns_completed}</td>
                <td class="px-4 py-2 text-right">${d.pending_deliveries}</td>
                <td class="px-4 py-2 text-right">${d.pending_returns}</td>
                <td class="px-4 py-2 text-right">${pct(d.checklist_pass_rate)}</td>
                <td class="px-4 py-2 text-right">${mins(d.avg_delivery_minutes)}</td>
                <td class="px-4 py-2 text-right">${mins(d.avg_return_minutes)}</td>
            </tr>
        `).join('');
    }

    // Initial render from server-provided data. Deferred to DOMContentLoaded
    // because ApexCharts is registered on window by the Vite module bundle,
    // which — being type="module" — always executes AFTER this inline
    // script (a classic script runs at its parse position, before any
    // deferred/module script). DOMContentLoaded is guaranteed to fire only
    // once all deferred/module scripts have finished running.
    document.addEventListener('DOMContentLoaded', () => renderAll(reportData));
})();
</script>
@endpush
