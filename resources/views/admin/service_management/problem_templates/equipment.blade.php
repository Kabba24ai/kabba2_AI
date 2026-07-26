@extends('admin.layouts.app')

@section('title', 'Equipment Template Assignment')

@section('content')
    @include('flash::message')

    <div class="max-w-6xl mx-auto px-4 pb-6">

        {{-- Sticky control bar: category → template → apply --}}
        <div class="sticky top-0 z-20 -mx-4 px-4 py-4 bg-white border-b border-gray-200 shadow-sm mb-5">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">Equipment Template Assignment</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Filter equipment by category, choose a Problem Template, then apply it to the selected units.</p>
                </div>
                <a href="{{ route('admin.service-management.problem-templates.index') }}" class="text-sm text-blue-600 hover:underline shrink-0 pt-1">← Problem Library</a>
            </div>

            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Equipment Category</label>
                    <select id="eq-category" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-56 outline-none focus:border-blue-400">
                        <option value="">Select Category</option>
                        <option value="all" @selected($selectedCategory === 'all')>All Categories</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected($selectedCategory === (string) $cat->id)>{{ $cat->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Problem Template</label>
                    <select id="eq-bulk-template" class="border border-gray-300 rounded-md px-3 py-2 text-sm w-56 outline-none focus:border-blue-400" @disabled($equipment->isEmpty())>
                        <option value="">Select Template</option>
                        @foreach ($templates as $t)
                            <option value="{{ $t->id }}">{{ $t->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Selection Action</label>
                    <button type="button" id="eq-bulk-apply" disabled
                        class="px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-40 disabled:cursor-not-allowed transition">
                        Apply to Selected
                    </button>
                </div>
                @if ($equipment->isNotEmpty())
                    <label class="flex items-center gap-2 text-sm text-gray-600 pb-2">
                        <input type="checkbox" id="eq-select-all" class="rounded text-blue-600 focus:ring-blue-500"> Select All Shown
                    </label>
                    <span class="text-xs text-gray-500 pb-2 ml-auto">
                        <span id="eq-selected-count"></span>
                        <span class="text-gray-400">{{ $equipment->count() }} equipment {{ $equipment->count() === 1 ? 'unit' : 'units' }} shown</span>
                    </span>
                @endif
            </div>
        </div>

        @if ($selectedCategory === '')
            {{-- No category chosen yet --}}
            <div class="bg-white border border-dashed border-gray-200 rounded-xl px-6 py-16 text-center">
                <x-heroicon-o-squares-2x2 class="w-8 h-8 text-gray-300 mx-auto mb-3" />
                <p class="text-sm text-gray-500">Select an equipment category to begin assigning a Problem Template.</p>
            </div>
        @elseif ($equipment->isEmpty())
            <div class="bg-white border border-gray-200 rounded-xl px-6 py-16 text-center">
                <p class="text-sm text-gray-400">No equipment found in this category.</p>
            </div>
        @else
            <div class="bg-white border border-gray-200 rounded-xl overflow-hidden">
                {{-- Table header --}}
                <div class="flex items-center gap-3 px-5 py-2.5 bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-400 uppercase tracking-wide">
                    <span class="w-4 shrink-0"></span>
                    <span class="flex-1">Equipment</span>
                    <span class="w-48 shrink-0">Current Template</span>
                    <span class="w-56 shrink-0">Assign Individually</span>
                </div>
                <div class="divide-y divide-gray-100">
                    @foreach ($equipment as $unit)
                        <div class="flex items-center gap-3 px-5 py-3" data-eq-row="{{ $unit->id }}">
                            <input type="checkbox" class="eq-check rounded text-blue-600 focus:ring-blue-500 shrink-0 w-4" value="{{ $unit->id }}">
                            <div class="flex-1 min-w-0">
                                <span class="text-sm font-medium text-gray-900">{{ $unit->equipment_name }}</span>
                                @if ($unit->equipment_id)<span class="text-xs text-gray-400"> ({{ $unit->equipment_id }})</span>@endif
                            </div>
                            <div class="w-48 shrink-0">
                                <span data-current-template class="text-sm {{ $unit->symptomProfile ? 'text-gray-700' : 'text-gray-400 italic' }}">
                                    {{ $unit->symptomProfile->name ?? 'No template' }}
                                </span>
                            </div>
                            <select data-eq-template class="w-56 shrink-0 border border-gray-300 rounded-md px-3 py-1.5 text-sm outline-none focus:border-blue-400">
                                <option value="">No template</option>
                                @foreach ($templates as $t)
                                    <option value="{{ $t->id }}" @selected($unit->service_symptom_profile_id === $t->id)>{{ $t->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = @json(csrf_token());
    const ATTACH_URL = @json(route('admin.service-management.problem-templates.equipment.attach'));
    const BULK_URL   = @json(route('admin.service-management.problem-templates.equipment.bulk-apply'));
    const BASE_URL   = @json(route('admin.service-management.problem-templates.equipment'));
    const TEMPLATES  = @json($templates->pluck('name', 'id')); // { id: name }
    const $ = id => document.getElementById(id);

    async function post(url, body) {
        const res = await fetch(url, { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: JSON.stringify(body) });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || 'Request failed.');
        return data;
    }
    const toast = (m, ok = true) => { if (window.notyf) (ok ? notyf.success(m) : notyf.error(m)); };
    const nameFor = id => (id && TEMPLATES[id]) ? TEMPLATES[id] : 'No template';

    function setRowCurrent(row, templateId) {
        const label = row.querySelector('[data-current-template]');
        if (!label) return;
        label.textContent = nameFor(templateId);
        label.classList.toggle('text-gray-700', !!templateId);
        label.classList.toggle('text-gray-400', !templateId);
        label.classList.toggle('italic', !templateId);
    }

    // ── Category filter (server reload; naturally clears selection) ──
    const cat = $('eq-category');
    if (cat) cat.addEventListener('change', function () {
        window.location = this.value ? (BASE_URL + '?category=' + encodeURIComponent(this.value)) : BASE_URL;
    });

    const checks = () => Array.from(document.querySelectorAll('.eq-check'));
    const bulkTemplate = $('eq-bulk-template');
    const applyBtn = $('eq-bulk-apply');

    function updateState() {
        const n = checks().filter(c => c.checked).length;
        const countEl = $('eq-selected-count');
        if (countEl) countEl.textContent = n ? (n + ' selected · ') : '';
        if (applyBtn) applyBtn.disabled = !(bulkTemplate && bulkTemplate.value && n > 0);
    }

    if (bulkTemplate) bulkTemplate.addEventListener('change', updateState);
    checks().forEach(c => c.addEventListener('change', updateState));

    const selectAll = $('eq-select-all');
    if (selectAll) selectAll.addEventListener('change', function () {
        checks().forEach(c => { c.checked = this.checked; });
        updateState();
    });

    // ── Bulk apply to checked (visible) units ──
    if (applyBtn) applyBtn.addEventListener('click', async function () {
        const ids = checks().filter(c => c.checked).map(c => Number(c.value));
        const tplId = bulkTemplate.value ? Number(bulkTemplate.value) : null;
        if (!ids.length || !tplId) return;
        const tplName = nameFor(tplId);
        if (!confirm('Assign "' + tplName + '" to ' + ids.length + ' selected equipment ' + (ids.length === 1 ? 'unit' : 'units') + '?')) return;
        applyBtn.disabled = true;
        try {
            const data = await post(BULK_URL, { equipment_ids: ids, template_id: tplId });
            const n = data.count ?? ids.length;
            // Update each affected row in place; keep category + template selected.
            checks().forEach(function (c) {
                if (!c.checked) return;
                const row = c.closest('[data-eq-row]');
                setRowCurrent(row, tplId);
                const sel = row.querySelector('[data-eq-template]'); if (sel) sel.value = String(tplId);
                c.checked = false;
            });
            if (selectAll) selectAll.checked = false;
            updateState();
            toast('"' + tplName + '" assigned to ' + n + ' equipment ' + (n === 1 ? 'unit' : 'units') + '.');
        } catch (e) { toast(e.message, false); applyBtn.disabled = false; }
    });

    // ── Individual row override (independent) ──
    document.querySelectorAll('[data-eq-row]').forEach(function (row) {
        const select = row.querySelector('[data-eq-template]');
        if (!select) return;
        select.addEventListener('change', async function () {
            const tplId = select.value ? Number(select.value) : null;
            try {
                await post(ATTACH_URL, { equipment_id: Number(row.dataset.eqRow), template_id: tplId });
                setRowCurrent(row, tplId);
                toast(tplId ? ('Assigned "' + nameFor(tplId) + '".') : 'Template removed.');
            } catch (e) { toast(e.message, false); }
        });
    });

    updateState();
});
</script>
@endpush
