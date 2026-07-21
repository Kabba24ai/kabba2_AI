@extends('admin.layouts.app')

@section('title', 'Equipment Templates')

@section('content')
    @include('flash::message')

    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Equipment Templates</h1>
                <p class="text-sm text-gray-500 mt-0.5">Attach one Reported-Problem template to each unit. The same template can go on many units at once.</p>
            </div>
            <a href="{{ route('admin.service-management.problem-templates.index') }}" class="text-sm text-blue-600 hover:underline">← Templates</a>
        </div>

        {{-- Filters --}}
        <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
            <input type="text" name="search" value="{{ $search }}" placeholder="Equipment name or ID"
                   class="border border-gray-300 rounded-md px-3 py-2 text-sm w-56">
            <select name="template" onchange="this.form.submit()" class="border border-gray-300 rounded-md px-3 py-2 text-sm">
                <option value="">All units</option>
                <option value="none" @selected($filter === 'none')>No template</option>
                @foreach ($templates as $t)
                    <option value="{{ $t->id }}" @selected((string) $filter === (string) $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2 text-sm rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50">Search</button>
        </form>

        {{-- Bulk apply bar --}}
        <div class="flex flex-wrap items-center gap-3 mb-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
            <label class="flex items-center gap-2 text-sm text-gray-600">
                <input type="checkbox" id="eq-select-all" class="rounded text-blue-600"> Select all shown
            </label>
            <span class="text-xs text-gray-400">|</span>
            <select id="eq-bulk-template" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm">
                <option value="">— Clear template —</option>
                @foreach ($templates as $t)
                    <option value="{{ $t->id }}">{{ $t->name }}</option>
                @endforeach
            </select>
            <button type="button" id="eq-bulk-apply" class="px-3 py-1.5 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Apply to selected</button>
            <span id="eq-selected-count" class="text-xs text-gray-500"></span>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
            @forelse ($equipment as $unit)
                <div class="flex items-center gap-3 px-5 py-3" data-eq-row="{{ $unit->id }}">
                    <input type="checkbox" class="eq-check rounded text-blue-600 shrink-0" value="{{ $unit->id }}">
                    <div class="flex-1 min-w-0">
                        <span class="text-sm font-medium text-gray-900">{{ $unit->equipment_name }}</span>
                        @if ($unit->equipment_id)<span class="text-xs text-gray-400"> ({{ $unit->equipment_id }})</span>@endif
                    </div>
                    <select data-eq-template class="border border-gray-300 rounded-md px-3 py-1.5 text-sm w-56 shrink-0">
                        <option value="">No template</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}" @selected($unit->service_symptom_profile_id === $t->id)>{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-400">No equipment found.</div>
            @endforelse
        </div>

        <div class="mt-4">{{ $equipment->links() }}</div>
    </div>
@endsection

@push('js')
<script>
(function () {
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const ATTACH_URL = @json(route('admin.service-management.problem-templates.equipment.attach'));
    const BULK_URL   = @json(route('admin.service-management.problem-templates.equipment.bulk-apply'));
    const $ = (id) => document.getElementById(id);

    async function post(url, body) {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed.');
        return data;
    }
    const toast = (m, ok = true) => { if (window.notyf) (ok ? notyf.success(m) : notyf.error(m)); };

    // Per-row change saves immediately
    document.querySelectorAll('[data-eq-row]').forEach(function (row) {
        const select = row.querySelector('[data-eq-template]');
        select.addEventListener('change', async function () {
            try {
                await post(ATTACH_URL, { equipment_id: Number(row.dataset.eqRow), template_id: select.value ? Number(select.value) : null });
                toast('Saved.');
            } catch (e) { toast(e.message, false); }
        });
    });

    // Bulk apply
    const checks = () => Array.from(document.querySelectorAll('.eq-check'));
    function syncCount() {
        const n = checks().filter(c => c.checked).length;
        $('eq-selected-count').textContent = n ? n + ' selected' : '';
    }
    $('eq-select-all').addEventListener('change', function () {
        checks().forEach(c => { c.checked = this.checked; });
        syncCount();
    });
    checks().forEach(c => c.addEventListener('change', syncCount));

    $('eq-bulk-apply').addEventListener('click', async function () {
        const ids = checks().filter(c => c.checked).map(c => Number(c.value));
        if (!ids.length) { toast('Select at least one unit.', false); return; }
        const templateId = $('eq-bulk-template').value ? Number($('eq-bulk-template').value) : null;
        try {
            const data = await post(BULK_URL, { equipment_ids: ids, template_id: templateId });
            toast(data.message || 'Applied.');
            setTimeout(() => window.location.reload(), 700);
        } catch (e) { toast(e.message, false); }
    });
})();
</script>
@endpush
