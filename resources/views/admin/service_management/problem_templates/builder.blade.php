@extends('admin.layouts.app')

@section('title', 'Template Builder')

@section('content')
    @include('flash::message')

    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">{{ $template->name }}</h1>
                <p class="text-sm text-gray-500 mt-0.5">{{ $template->description ?: 'Assemble this template — add items from any category, order them, reuse freely.' }}</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.service-management.problem-templates.index') }}" class="text-sm text-blue-600 hover:underline">← Templates</a>
                <span id="bld-status" class="text-xs text-gray-400"></span>
                <button type="button" id="bld-save" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-40">Save Template</button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Library (add from) --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-sm font-semibold text-gray-800">Library</h2>
                    <input type="text" id="bld-search" placeholder="Filter items…" class="border border-gray-300 rounded-md px-3 py-1.5 text-sm w-40">
                </div>
                <div id="bld-library" class="space-y-3 max-h-[32rem] overflow-y-auto">
                    @foreach ($categories as $category)
                        @php $catItems = $items->where('service_symptom_category_id', $category->id); @endphp
                        @if ($catItems->isNotEmpty())
                            <div data-cat-group>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1.5">{{ $category->name }}</p>
                                <div class="space-y-1">
                                    @foreach ($catItems as $item)
                                        <button type="button" data-add-item="{{ $item->id }}" data-name="{{ $item->name }}"
                                                class="w-full text-left flex items-center gap-2 px-2.5 py-1.5 rounded-md border border-gray-200 text-sm text-gray-700 hover:bg-blue-50 hover:border-blue-200">
                                            <x-heroicon-o-plus class="w-3.5 h-3.5 text-blue-500 shrink-0" />
                                            <span class="truncate">{{ $item->name }}</span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>

            {{-- Selected (this template, ordered) --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">In this template <span id="bld-count" class="text-gray-400 font-normal"></span></h2>
                <div id="bld-selected" class="space-y-1 max-h-[32rem] overflow-y-auto"></div>
                <p id="bld-empty" class="text-sm text-gray-400 py-8 text-center hidden">No items yet — add them from the library.</p>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const SAVE_URL = @json(route('admin.service-management.problem-templates.save-items', $template));
    const ITEMS = @json($items->map(fn ($i) => ['id' => $i->id, 'name' => $i->name])->keyBy('id'));
    let selected = @json($selectedItemIds); // ordered array of ids

    const $ = (id) => document.getElementById(id);
    const box = $('bld-selected');
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c]));

    function nameOf(id) { return (ITEMS[id] && ITEMS[id].name) || ('#' + id); }

    function render() {
        box.replaceChildren();
        $('bld-empty').classList.toggle('hidden', selected.length > 0);
        $('bld-count').textContent = selected.length ? '(' + selected.length + ')' : '';
        selected.forEach(function (id, i) {
            const row = document.createElement('div');
            row.className = 'flex items-center gap-2 px-2.5 py-1.5 rounded-md border border-gray-200 bg-gray-50';
            row.innerHTML =
                '<div class="flex flex-col shrink-0">'
                + '<button type="button" data-move="up" class="text-gray-400 hover:text-gray-700 leading-none ' + (i === 0 ? 'invisible' : '') + '">▲</button>'
                + '<button type="button" data-move="down" class="text-gray-400 hover:text-gray-700 leading-none ' + (i === selected.length - 1 ? 'invisible' : '') + '">▼</button>'
                + '</div>'
                + '<span class="flex-1 min-w-0 text-sm text-gray-800 truncate">' + esc(nameOf(id)) + '</span>'
                + '<button type="button" data-remove class="text-red-400 hover:text-red-600 text-sm font-bold shrink-0">×</button>';
            row.dataset.id = id;
            box.appendChild(row);
        });
        // Dim library items already in the template
        document.querySelectorAll('[data-add-item]').forEach(function (btn) {
            const inUse = selected.includes(Number(btn.dataset.addItem));
            btn.classList.toggle('opacity-40', inUse);
            btn.classList.toggle('pointer-events-none', inUse);
        });
    }

    document.getElementById('bld-library').addEventListener('click', function (e) {
        const btn = e.target.closest('[data-add-item]'); if (!btn) return;
        const id = Number(btn.dataset.addItem);
        if (!selected.includes(id)) { selected.push(id); render(); markDirty(); }
    });

    box.addEventListener('click', function (e) {
        const row = e.target.closest('[data-id]'); if (!row) return;
        const id = Number(row.dataset.id);
        if (e.target.matches('[data-remove]')) {
            selected = selected.filter(x => x !== id); render(); markDirty();
        } else if (e.target.closest('[data-move]')) {
            const dir = e.target.closest('[data-move]').dataset.move;
            const i = selected.indexOf(id), j = dir === 'up' ? i - 1 : i + 1;
            if (j < 0 || j >= selected.length) return;
            [selected[i], selected[j]] = [selected[j], selected[i]]; render(); markDirty();
        }
    });

    $('bld-search').addEventListener('input', function () {
        const q = this.value.trim().toLowerCase();
        document.querySelectorAll('#bld-library [data-cat-group]').forEach(function (group) {
            let anyVisible = false;
            group.querySelectorAll('[data-add-item]').forEach(function (btn) {
                const show = btn.dataset.name.toLowerCase().includes(q);
                btn.classList.toggle('hidden', !show);
                if (show) anyVisible = true;
            });
            group.classList.toggle('hidden', !anyVisible);
        });
    });

    let dirty = false;
    function markDirty() { dirty = true; $('bld-status').textContent = 'Unsaved changes'; }

    $('bld-save').addEventListener('click', async function () {
        $('bld-save').disabled = true;
        $('bld-status').textContent = 'Saving…';
        try {
            const res = await fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ item_ids: selected }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) throw new Error(data.message || 'Save failed.');
            dirty = false;
            $('bld-status').textContent = 'Saved';
            if (window.notyf) notyf.success('Template saved (' + data.count + ' items).');
        } catch (e) {
            $('bld-status').textContent = '';
            if (window.notyf) notyf.error(e.message); else alert(e.message);
        } finally {
            $('bld-save').disabled = false;
        }
    });

    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    render();
})();
</script>
@endpush
