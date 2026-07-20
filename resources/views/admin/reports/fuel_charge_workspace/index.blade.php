@extends('admin.layouts.app')

@section('title', 'Fuel Charge Workspace')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Dashboard V2 Phase 1A — the Fuel Charge operational workspace.
         Centered container: compact mini-dashboard, filters, then the
         full-width queue. All actions post to the canonical dashboard
         endpoints; metrics come from the same ChargeAlertQueue service
         the dashboard card consumes, so the two always reconcile. --}}
    <div class="max-w-6xl mx-auto px-4 py-6">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Fuel Charge Workspace</h1>
                <p class="text-sm text-gray-500 mt-0.5">Review, adjust, collect, and resolve fuel charge alerts.</p>
            </div>
            <div class="flex items-center gap-4">
                <button type="button" id="ws-new-fuel-charge"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    New Fuel Charge
                </button>
                <a href="{{ route('admin.dashboard.index') }}" class="text-sm text-blue-600 hover:underline">← Dashboard</a>
            </div>
        </div>

        {{-- Mini dashboard (compact — operational awareness only) --}}
        <div class="mb-5" data-summary-wrap>
            @include('admin.reports.fuel_charge_workspace.partials._summary', ['summary' => $summary])
        </div>

        {{-- Filters --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
            <form id="ws-filters" class="grid grid-cols-1 sm:grid-cols-4 gap-3" onsubmit="return false;">
                <input type="text" name="search_name" value="{{ request('search_name') }}" placeholder="Customer name"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                <input type="text" name="search_order" value="{{ request('search_order') }}" placeholder="Order #"
                       class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                <select name="status" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <option value="active" @selected($status === 'active')>Active (queue)</option>
                    <option value="resolved" @selected($status === 'resolved')>Resolved</option>
                    <option value="completed" @selected($status === 'completed')>Completed</option>
                    <option value="uncollectible" @selected($status === 'uncollectible')>Uncollectible</option>
                </select>
                <button type="button" id="ws-clear-filters"
                        class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                    Clear Filters
                </button>
            </form>
        </div>

        {{-- Queue --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4" data-queue-wrap>
            @include('admin.reports.fuel_charge_workspace.partials._queue', ['alerts' => $alerts])
        </div>
    </div>

    @include('admin.charges._action_modals', ['chargeType' => 'fuel'])

    @include('admin.charges._new_fuel_charge_modal', [
        'users' => $users,
        'fuelNotePresets' => $fuelNotePresets,
        'nfcContext' => 'fuel_workspace',
    ])

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const form = document.getElementById('ws-filters');
    let page = {{ (int) request('page', 1) }};

    window.wsCurrentFilters = function () {
        const data = Object.fromEntries(new FormData(form).entries());
        if (page > 1) data.page = page;
        return data;
    };

    // In-place queue + summary refresh, preserving current filters/page.
    // Plugged into the shared action bundle below so every charge action
    // refreshes fragments instead of reloading the page.
    async function refreshWorkspace() {
        const params = new URLSearchParams(window.wsCurrentFilters());
        params.set('fragment', '1');
        const res = await fetch(`${window.location.pathname}?${params}`, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        document.querySelector('[data-queue-wrap]').innerHTML = data.queue_html;
        document.querySelector('[data-summary-wrap]').innerHTML = data.summary_html;
        window.ChargeActions.bind();
    }
    window.wsRefreshWorkspace = refreshWorkspace;
    // Late-bound so the pagination-rebinding wrapper below is honored.
    window.ChargeActions.onChanged = () => window.wsRefreshWorkspace();

    async function reload(resetPage = true) {
        if (resetPage) page = 1;
        await window.wsRefreshWorkspace();
        bindPagination();
    }

    let debounce;
    form.querySelectorAll('input').forEach((el) =>
        el.addEventListener('input', () => { clearTimeout(debounce); debounce = setTimeout(() => reload(), 350); }));
    form.querySelector('select[name="status"]').addEventListener('change', () => reload());

    document.getElementById('ws-clear-filters').addEventListener('click', () => {
        form.reset();
        form.querySelector('select[name="status"]').value = 'active';
        reload();
    });

    // AJAX pagination — intercept the paginator links inside the queue wrap.
    function bindPagination() {
        document.querySelectorAll('[data-pagination] a').forEach((a) => {
            a.addEventListener('click', (e) => {
                e.preventDefault();
                const url = new URL(a.href);
                page = Number(url.searchParams.get('page') || 1);
                window.wsRefreshWorkspace().then(bindPagination);
            });
        });
    }
    bindPagination();

    // Re-bind pagination after any workspace refresh triggered by actions.
    const origRefresh = window.wsRefreshWorkspace;
    window.wsRefreshWorkspace = async function () {
        await origRefresh();
        bindPagination();
    };

    // Shared New Fuel Charge modal wiring: refresh queue + metrics (filters
    // preserved) after creation; "Save & Collect Payment" continues into
    // the canonical payment modal as a CRM-source payment.
    document.getElementById('ws-new-fuel-charge').addEventListener('click', () => {
        window.NewFuelCharge.open({});
    });
    window.NewFuelCharge.onCreated = async function (charge, continueToPayment) {
        await window.wsRefreshWorkspace();
        if (continueToPayment) {
            window.BillingPayment.openForCharge(charge, 'New fuel charge');
        }
    };
})();
</script>
@endpush
