@extends('admin.layouts.app')

@section('title', 'Damage Charge Resolution')

@section('content')

    @include('flash::message')
    @include('admin.partials.formErrors')

    {{-- Task Manager → Billing Operations → Damage Charge Resolution.
         A sibling of the Fuel workspace: same shell, same shared queue/
         summary/action components, SEPARATE damage queue. Metrics come from
         the same ChargeAlertQueue::damageAlerts() the dashboard Damage card
         consumes, so the two always reconcile. Damage evidence and
         investigation stay in the originating Customer Checklist / Service
         Ticket — each row links back via its Source. --}}
    <div class="max-w-6xl mx-auto px-4 py-6">

        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Damage Charge Resolution</h1>
                <p class="text-sm text-gray-500 mt-0.5">Review, collect, and resolve damage charge alerts. Damage details live in the originating checklist or service ticket.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.dashboard.index') }}" class="text-sm text-blue-600 hover:underline">← Dashboard</a>
            </div>
        </div>

        {{-- Mini dashboard (compact — operational awareness only) --}}
        <div class="mb-5" data-summary-wrap>
            @include('admin.tasks.billing.partials._summary', ['summary' => $summary])
        </div>

        @php $needsPricingActive = request()->boolean('needs_pricing'); @endphp

        {{-- Filters --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4 mb-5">
            <form id="ws-filters" onsubmit="return false;">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
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
                </div>
                <div class="mt-3 flex items-center justify-between gap-3">
                    <label class="inline-flex items-center gap-2 text-sm rounded-md border px-3 py-2 cursor-pointer {{ $needsPricingActive ? 'border-amber-400 bg-amber-50 text-amber-800 font-medium' : 'border-gray-300 text-gray-600' }}">
                        <input type="checkbox" name="needs_pricing" value="1" @checked($needsPricingActive)>
                        Needs Pricing only
                    </label>
                    <button type="button" id="ws-clear-filters"
                            class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">
                        Clear Filters
                    </button>
                </div>
            </form>
        </div>

        {{-- Active-filter banner + clear way back --}}
        @if ($needsPricingActive)
            <div class="mb-3 flex items-center justify-between rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm text-amber-800"
                 data-active-filter="needs_pricing">
                <span>Showing only damage items that need pricing.</span>
                <a href="{{ route('admin.tasks.billing.damage-charges.index') }}" class="font-medium underline">Show all active</a>
            </div>
        @endif

        {{-- Queue --}}
        <div class="bg-white border border-gray-200 rounded-xl p-4" data-queue-wrap>
            @include('admin.tasks.billing.partials._queue', ['alerts' => $alerts, 'chargeType' => 'damage'])
        </div>
    </div>

    @include('admin.charges._action_modals', ['chargeType' => 'damage'])

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
        // form.reset() restores the initial DOM state — explicitly clear the
        // Needs Pricing filter so a deep-linked (?needs_pricing=1) page clears.
        const np = form.querySelector('input[name="needs_pricing"]');
        if (np) np.checked = false;
        const banner = document.querySelector('[data-active-filter="needs_pricing"]');
        if (banner) banner.remove();
        reload();
    });

    // Also drop the active-filter banner when the checkbox is unticked in-page.
    const npBox = form.querySelector('input[name="needs_pricing"]');
    if (npBox) {
        npBox.addEventListener('change', () => {
            if (!npBox.checked) {
                const banner = document.querySelector('[data-active-filter="needs_pricing"]');
                if (banner) banner.remove();
            }
        });
    }

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

    const origRefresh = window.wsRefreshWorkspace;
    window.wsRefreshWorkspace = async function () {
        await origRefresh();
        bindPagination();
    };
})();
</script>
@endpush
