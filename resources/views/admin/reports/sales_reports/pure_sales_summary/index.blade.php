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
        <div class="flex flex-wrap items-end gap-3">

            {{-- Clear --}}
            <button type="button" id="btn-clear-filters"
                class="text-sm text-gray-600 bg-white px-3 py-2 flex gap-2 items-center rounded-md border border-gray-300 hover:bg-gray-50">
                <x-heroicon-o-x-mark class="w-4 h-4" /> Clear
            </button>

            {{-- Date Range Preset --}}
            <div>
                <label class="block text-xs text-gray-500 mb-1">Date Range</label>
                <select id="f-date-range"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value=""           @selected(($filters['date_range'] ?? '') === '')>All Time</option>
                    <option value="today"      @selected(($filters['date_range'] ?? '') === 'today')>Today</option>
                    <option value="yesterday"  @selected(($filters['date_range'] ?? '') === 'yesterday')>Yesterday</option>
                    <option value="last_7"     @selected(($filters['date_range'] ?? '') === 'last_7')>Last 7 Days</option>
                    <option value="last_30"    @selected(($filters['date_range'] ?? '') === 'last_30')>Last 30 Days</option>
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

            {{-- Run Report --}}
            <div class="pt-4">
                <button type="button" id="btn-run-report"
                    class="px-5 py-2 bg-brand-500 hover:bg-brand-600 text-white text-sm font-medium rounded-lg transition">
                    Run Report
                </button>
            </div>
        </div>

        {{-- Revenue Inclusion Filters (second row) --}}
        <div class="flex flex-wrap gap-6 mt-4 pt-4 border-t border-gray-100 dark:border-gray-700">

            {{-- Payment Status --}}
            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Payment Status</p>
                <select id="f-payment-status"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="paid"    @selected(($filters['payment_status'] ?? 'paid') === 'paid')>Paid Orders</option>
                    <option value="all"     @selected(($filters['payment_status'] ?? '') === 'all')>All Orders</option>
                    <option value="pod"     @selected(($filters['payment_status'] ?? '') === 'pod')>POD (COD Only)</option>
                    <option value="account" @selected(($filters['payment_status'] ?? '') === 'account')>Account Orders</option>
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Damage Waiver</p>
                <select id="f-damage-waiver"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all"     @selected(($filters['damage_waiver'] ?? 'all') === 'all')>Include All</option>
                    <option value="only"    @selected(($filters['damage_waiver'] ?? '') === 'only')>Damage Waiver Only</option>
                    <option value="exclude" @selected(($filters['damage_waiver'] ?? '') === 'exclude')>Exclude Damage Waiver</option>
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Track Insurance</p>
                <select id="f-track-insurance"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all"     @selected(($filters['track_insurance'] ?? 'all') === 'all')>Include All</option>
                    <option value="only"    @selected(($filters['track_insurance'] ?? '') === 'only')>Track Insurance Only</option>
                    <option value="exclude" @selected(($filters['track_insurance'] ?? '') === 'exclude')>Exclude Track Insurance</option>
                </select>
            </div>

            <div>
                <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Delivery</p>
                <select id="f-delivery"
                    class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                    <option value="all"     @selected(($filters['delivery'] ?? 'all') === 'all')>Include All</option>
                    <option value="only"    @selected(($filters['delivery'] ?? '') === 'only')>Delivery Only</option>
                    <option value="exclude" @selected(($filters['delivery'] ?? '') === 'exclude')>Exclude Delivery</option>
                </select>
            </div>

        </div>
    </div>

    {{-- ── KPI CARDS ────────────────────────────────────────────────────────── --}}
    <div id="kpi-section">
        @include('admin.reports.sales_reports.pure_sales_summary.partials._kpi_cards', [
            'kpis'           => $kpis,
            'dateRangeLabel' => $dateRangeLabel,
        ])
    </div>

    {{-- ── DETAIL GRID ──────────────────────────────────────────────────────── --}}
    <div id="detail-section">
        @include('admin.reports.sales_reports.pure_sales_summary.partials._detail_table', [
            'grid' => $grid,
        ])
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const ROUTE = '{{ route('admin.reports.sales-reports.pure-sales-summary.index') }}';
    const EXPORT_ROUTE = '{{ route('admin.reports.sales-reports.pure-sales-summary.export') }}';

    // ── DOM refs ──────────────────────────────────────────────────────────────
    const fDateRange      = document.getElementById('f-date-range');
    const fStartDate      = document.getElementById('f-start-date');
    const fEndDate        = document.getElementById('f-end-date');
    const fStore          = document.getElementById('f-store');
    const fItemType       = document.getElementById('f-item-type');
    const fCategory       = document.getElementById('f-category');
    const fProduct        = document.getElementById('f-product');
    const fPaymentStatus  = document.getElementById('f-payment-status');
    const fDamageWaiver   = document.getElementById('f-damage-waiver');
    const fTrackInsurance = document.getElementById('f-track-insurance');
    const fDelivery       = document.getElementById('f-delivery');
    const customDateWrap  = document.getElementById('custom-date-wrap');
    const kpiSection      = document.getElementById('kpi-section');
    const detailSection   = document.getElementById('detail-section');

    // ── Helpers ───────────────────────────────────────────────────────────────
    function collectFilters() {
        const params = new URLSearchParams();
        const dr = fDateRange.value;
        if (dr) params.set('date_range', dr);
        if (dr === 'custom') {
            if (fStartDate.value) params.set('start_date', fStartDate.value);
            if (fEndDate.value)   params.set('end_date', fEndDate.value);
        }
        if (fStore.value)          params.set('store', fStore.value);
        if (fItemType.value !== 'all') params.set('item_type', fItemType.value);
        if (fCategory.value)       params.set('category', fCategory.value);
        if (fProduct.value)        params.set('product', fProduct.value);
        params.set('payment_status', fPaymentStatus.value || 'paid');
        if (fDamageWaiver.value !== 'all')   params.set('damage_waiver', fDamageWaiver.value);
        if (fTrackInsurance.value !== 'all') params.set('track_insurance', fTrackInsurance.value);
        if (fDelivery.value !== 'all')       params.set('delivery', fDelivery.value);
        return params;
    }

    function setLoading(on) {
        kpiSection.style.opacity    = on ? '0.4' : '1';
        detailSection.style.opacity = on ? '0.4' : '1';
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
            if (data.kpi_html) kpiSection.innerHTML   = data.kpi_html;
            if (data.html)     detailSection.innerHTML = data.html;
            // Update export link
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

    // ── Custom date range toggle ───────────────────────────────────────────────
    fDateRange.addEventListener('change', function () {
        customDateWrap.classList.toggle('hidden', this.value !== 'custom');
    });

    // ── Category → Product dependency ─────────────────────────────────────────
    fCategory.addEventListener('change', function () {
        const catId = this.value;
        fProduct.innerHTML = '<option value="">All Products</option>';
        if (!catId) return;

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
        })
        .catch(err => console.error('Product load error:', err));
    });

    // ── Run on button click ────────────────────────────────────────────────────
    document.getElementById('btn-run-report').addEventListener('click', () => runReport(1));

    // ── Clear filters ──────────────────────────────────────────────────────────
    document.getElementById('btn-clear-filters').addEventListener('click', function () {
        fDateRange.value      = 'mtd';
        fStartDate.value      = '';
        fEndDate.value        = '';
        fStore.value          = '';
        fItemType.value       = 'all';
        fCategory.value       = '';
        fProduct.value        = '';
        fPaymentStatus.value  = 'paid';
        fDamageWaiver.value   = 'all';
        fTrackInsurance.value = 'all';
        fDelivery.value       = 'all';
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

    // ── AJAX products endpoint ─────────────────────────────────────────────────
    // Handled in the same IndexController via ?ajax_products=1 query param.

})();
</script>
@endpush
