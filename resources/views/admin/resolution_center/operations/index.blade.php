@extends('admin.layouts.app')

@section('title', 'Resolution Operations Center')

@section('content')

    @include('flash::message')

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-semibold text-gray-900">Resolution Operations Center</h2>
        <a href="{{ route('admin.resolution-center.index') }}" class="text-sm text-blue-600 hover:underline">Full Case History</a>
    </div>

    {{-- Dashboard Metrics — operational only, not historical reporting --}}
    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Open Cases</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['open_cases'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Waiting on Customer</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['waiting_on_customer'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Waiting on Employee</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['waiting_on_employee'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Manager Review Required</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['manager_review_required'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Completed Today</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['completed_today'] }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Avg. Resolution Time</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['average_resolution_hours'] !== null ? $metrics['average_resolution_hours'].'h' : '—' }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Oldest Open Case</div>
            <div class="text-2xl font-semibold text-gray-900">{{ $metrics['oldest_open_case_age_days'] !== null ? $metrics['oldest_open_case_age_days'].'d' : '—' }}</div>
        </div>
        <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
            <div class="text-[11px] uppercase tracking-wide text-gray-400">Store Credit Issued Today</div>
            <div class="text-2xl font-semibold text-gray-900">{{ \App\Helpers\CustomHelper::formatCurrency($metrics['store_credit_issued_today']) }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="flex flex-wrap items-end gap-4 w-full mb-6 bg-white rounded-xl p-5 shadow-sm border border-gray-100">
        <div class="w-full sm:w-auto">
            <button type="button" id="clear-filters" class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">Clear</button>
        </div>

        <div class="w-full sm:w-40">
            <label class="block text-xs text-gray-500 mb-1">Status</label>
            <select name="status" id="status_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">All</option>
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="waiting">Waiting</option>
                <option value="escalated">Escalated</option>
                <option value="completed">Completed</option>
            </select>
        </div>

        <div class="w-full sm:w-40">
            <label class="block text-xs text-gray-500 mb-1">Priority</label>
            <select name="priority" id="priority_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">All</option>
                <option value="low">Low</option>
                <option value="normal">Normal</option>
                <option value="high">High</option>
                <option value="urgent">Urgent</option>
            </select>
        </div>

        <div class="w-full sm:w-48">
            <label class="block text-xs text-gray-500 mb-1">Assigned To</label>
            <select name="assigned_to" id="assigned_to_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">Anyone</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <label class="block text-xs text-gray-500 mb-1">Scenario</label>
            <select name="scenario_key" id="scenario_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">All</option>
                @foreach($scenarios as $scenario)
                    <option value="{{ $scenario->key() }}">{{ $scenario->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <label class="block text-xs text-gray-500 mb-1">Issue Category</label>
            <select name="issue_category" id="issue_category_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">All</option>
                @foreach($issueCategories as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-48">
            <label class="block text-xs text-gray-500 mb-1">Store</label>
            <select name="store_id" id="store_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300">
                <option value="">All</option>
                @foreach($stores as $store)
                    <option value="{{ $store->id }}">{{ $store->store_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-40">
            <label class="block text-xs text-gray-500 mb-1">From</label>
            <input type="date" name="date_from" id="date_from_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300" />
        </div>
        <div class="w-full sm:w-40">
            <label class="block text-xs text-gray-500 mb-1">To</label>
            <input type="date" name="date_to" id="date_to_filter" class="w-full border rounded-md px-3 py-2 text-sm border-gray-300" />
        </div>

        <div class="w-full sm:w-auto px-4 py-3 rounded-md border border-gray-300 text-sm text-gray-900">
            Total: <span id="ops-total-count"></span>
        </div>
    </div>

    <div id="ops-table-wrapper">
        @include('admin.resolution_center.operations.partials._table', ['cases' => []])
    </div>

    {{-- Shared Assign/Reassign modal — copies Dispatch's driver-assignment-modal interaction pattern --}}
    @can('resolution_center.override')
    <div id="assignCaseModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40">
        <div class="bg-white rounded-xl shadow-lg p-6 w-full max-w-sm">
            <h3 class="font-semibold text-lg mb-3">Assign Case</h3>
            <select id="assign-employee-select" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-4">
                <option value="">— Unassign / Clear —</option>
                @foreach($employees as $employee)
                    <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                @endforeach
            </select>
            <div class="flex justify-end gap-2">
                <button type="button" id="assign-cancel-btn" class="px-4 py-2 rounded-md border border-gray-300 text-sm">Cancel</button>
                <button type="button" id="assign-save-btn" class="px-4 py-2 rounded-md bg-blue-600 text-white text-sm font-medium">Save</button>
            </div>
        </div>
    </div>
    @endcan

@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const wrapper = document.querySelector('#ops-table-wrapper');
    const totalEl = document.querySelector('#ops-total-count');
    const screenKey = 'resolution_ops_filters';
    const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    // Route URLs built with a placeholder unique_id, swapped in at call
    // time — avoids constructing admin URL prefixes by hand in JS.
    const routeTemplates = {
        assign: "{{ route('admin.resolution-center.assign', 'PLACEHOLDER') }}",
        priority: "{{ route('admin.resolution-center.priority', 'PLACEHOLDER') }}",
        wait: "{{ route('admin.resolution-center.wait', 'PLACEHOLDER') }}",
        escalate: "{{ route('admin.resolution-center.escalate', 'PLACEHOLDER') }}",
        close: "{{ route('admin.resolution-center.close', 'PLACEHOLDER') }}",
        reopen: "{{ route('admin.resolution-center.reopen', 'PLACEHOLDER') }}",
    };
    function actionUrl(action, uniqueId) {
        return routeTemplates[action].replace('PLACEHOLDER', uniqueId);
    }

    const fieldMap = {
        'status': document.getElementById('status_filter'),
        'priority': document.getElementById('priority_filter'),
        'assigned_to': document.getElementById('assigned_to_filter'),
        'scenario_key': document.getElementById('scenario_filter'),
        'issue_category': document.getElementById('issue_category_filter'),
        'store_id': document.getElementById('store_filter'),
        'date_from': document.getElementById('date_from_filter'),
        'date_to': document.getElementById('date_to_filter'),
    };

    if (window.FilterFreezer) {
        FilterFreezer.loadFilters(screenKey, fieldMap);
    }

    function fetchCases(page = 1, perPage = 25) {
        const params = new URLSearchParams();
        Object.entries(fieldMap).forEach(([key, el]) => {
            if (el && el.value) params.append(key, el.value);
        });
        params.append('per_page', perPage);
        params.append('page', page);

        wrapper.classList.add('opacity-50', 'pointer-events-none');

        fetch("{{ route('admin.resolution-center.operations') }}?" + params.toString(), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(response => response.json())
            .then(data => {
                wrapper.innerHTML = data.html;
                totalEl.textContent = data.total;
                bindRowActions();
            })
            .catch(() => {
                wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading Resolution Cases.</div>';
            })
            .finally(() => {
                wrapper.classList.remove('opacity-50', 'pointer-events-none');
            });

        if (window.FilterFreezer) {
            FilterFreezer.saveFilters(screenKey, fieldMap);
        }
    }

    document.getElementById('clear-filters').addEventListener('click', function () {
        if (window.clearFilters) {
            window.clearFilters(fieldMap, screenKey);
        } else {
            Object.values(fieldMap).forEach(el => { if (el) el.value = ''; });
        }
        fetchCases(1);
    });

    Object.values(fieldMap).forEach(el => {
        if (el) el.addEventListener('change', () => fetchCases(1));
    });

    if (window.Paginator) {
        Paginator.init({ wrapper: wrapper, fetchCallback: fetchCases });
    }

    let currentAssignUniqueId = null;
    const assignModal = document.getElementById('assignCaseModal');

    function bindRowActions() {
        document.querySelectorAll('[data-action="open-assign"]').forEach(btn => {
            btn.addEventListener('click', function () {
                currentAssignUniqueId = this.dataset.uniqueId;
                document.getElementById('assign-employee-select').value = this.dataset.currentAssignee || '';
                assignModal.classList.remove('hidden');
                assignModal.classList.add('flex');
            });
        });

        document.querySelectorAll('[data-action="quick-status"]').forEach(btn => {
            btn.addEventListener('click', function () {
                const uniqueId = this.dataset.uniqueId;
                const endpoint = this.dataset.endpoint;
                window.apiFetch(actionUrl(endpoint, uniqueId), {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                }).then(() => fetchCases());
            });
        });
    }

    document.getElementById('assign-cancel-btn')?.addEventListener('click', function () {
        assignModal.classList.add('hidden');
        assignModal.classList.remove('flex');
    });

    document.getElementById('assign-save-btn')?.addEventListener('click', function () {
        const employeeId = document.getElementById('assign-employee-select').value;
        window.apiFetch(actionUrl('assign', currentAssignUniqueId), {
            method: 'POST',
            body: JSON.stringify({ assigned_to_user_id: employeeId || null }),
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
        }).then(() => {
            assignModal.classList.add('hidden');
            assignModal.classList.remove('flex');
            fetchCases();
        });
    });

    bindRowActions();
});
</script>
@endpush
