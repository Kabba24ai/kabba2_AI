@extends('admin.layouts.app')

@section('title', 'Problem Library')

@section('content')
    @include('flash::message')

    <div class="max-w-6xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Problem Library</h1>
                <p class="text-sm text-gray-500 mt-0.5">Categories and the symptoms within them — the building blocks assembled into Reported-Problem templates.</p>
            </div>
            <a href="{{ route('admin.service-management.problem-templates.index') }}" class="text-sm text-blue-600 hover:underline">Templates →</a>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Categories --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Categories</h2>
                <div class="flex items-center gap-2 mb-3">
                    <input type="text" id="cat-new" maxlength="255" placeholder="New category…"
                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <button type="button" id="cat-add" class="px-3 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 shrink-0">Add</button>
                </div>
                <div id="cat-feedback" class="hidden mb-2 text-xs rounded-md px-3 py-2"></div>
                <div id="cat-list" class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-[28rem] overflow-y-auto">
                    @foreach ($categories as $category)
                        <div class="flex items-center gap-2 px-3 py-2" data-cat-row="{{ $category->id }}">
                            <div class="flex flex-col shrink-0">
                                <button type="button" data-cat-move="up" class="text-gray-400 hover:text-gray-700 leading-none">▲</button>
                                <button type="button" data-cat-move="down" class="text-gray-400 hover:text-gray-700 leading-none">▼</button>
                            </div>
                            <input type="text" value="{{ $category->name }}" maxlength="255"
                                   class="flex-1 min-w-0 border border-gray-200 rounded-md px-2 py-1.5 text-sm {{ $category->is_active ? '' : 'text-gray-400 line-through' }}" data-cat-name>
                            <span class="text-xs text-gray-400 shrink-0">{{ $category->symptoms_count }} items</span>
                            <button type="button" data-cat-save class="px-2 py-1.5 text-xs rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 shrink-0">Save</button>
                            <button type="button" data-cat-toggle data-active="{{ $category->is_active ? '1' : '0' }}"
                                    class="px-2 py-1.5 text-xs rounded-md border shrink-0 {{ $category->is_active ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                {{ $category->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button type="button" data-cat-delete class="px-2 py-1.5 text-xs rounded-md border border-red-200 text-red-600 hover:bg-red-50 shrink-0">Delete</button>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- Items --}}
            <div class="bg-white border border-gray-200 rounded-xl p-5">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Items (Symptoms)</h2>
                <div class="flex items-center gap-2 mb-3">
                    <select id="item-new-cat" class="border border-gray-300 rounded-md px-2 py-2 text-sm">
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" id="item-new" maxlength="255" placeholder="New item…"
                           class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm">
                    <button type="button" id="item-add" class="px-3 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700 shrink-0">Add</button>
                </div>
                <div id="item-feedback" class="hidden mb-2 text-xs rounded-md px-3 py-2"></div>
                <div id="item-list" class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-[28rem] overflow-y-auto">
                    @foreach ($items as $item)
                        <div class="flex items-center gap-2 px-3 py-2" data-item-row="{{ $item->id }}" data-cat="{{ $item->service_symptom_category_id }}">
                            <input type="text" value="{{ $item->name }}" maxlength="255"
                                   class="flex-1 min-w-0 border border-gray-200 rounded-md px-2 py-1.5 text-sm {{ $item->is_active ? '' : 'text-gray-400 line-through' }}" data-item-name>
                            <select data-item-cat class="border border-gray-200 rounded-md px-2 py-1.5 text-xs text-gray-600 shrink-0">
                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" @selected($category->id === $item->service_symptom_category_id)>{{ $category->name }}</option>
                                @endforeach
                            </select>
                            <button type="button" data-item-save class="px-2 py-1.5 text-xs rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 shrink-0">Save</button>
                            <button type="button" data-item-toggle data-active="{{ $item->is_active ? '1' : '0' }}"
                                    class="px-2 py-1.5 text-xs rounded-md border shrink-0 {{ $item->is_active ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                                {{ $item->is_active ? 'Deactivate' : 'Activate' }}
                            </button>
                            <button type="button" data-item-delete class="px-2 py-1.5 text-xs rounded-md border border-red-200 text-red-600 hover:bg-red-50 shrink-0">Delete</button>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
(function () {
    'use strict';
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const URLS = {
        catStore:   @json(route('admin.service-management.problem-templates.categories.store')),
        catUpdate:  @json(route('admin.service-management.problem-templates.categories.update', ':id')),
        catDestroy: @json(route('admin.service-management.problem-templates.categories.destroy', ':id')),
        catReorder: @json(route('admin.service-management.problem-templates.categories.reorder')),
        itemStore:   @json(route('admin.service-management.problem-templates.items.store')),
        itemUpdate:  @json(route('admin.service-management.problem-templates.items.update', ':id')),
        itemDestroy: @json(route('admin.service-management.problem-templates.items.destroy', ':id')),
        itemReorder: @json(route('admin.service-management.problem-templates.items.reorder')),
    };

    async function req(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Request failed.');
        }
        return data;
    }
    function feedback(id, msg, ok) {
        const box = document.getElementById(id);
        box.textContent = msg;
        box.className = 'mb-2 text-xs rounded-md px-3 py-2 border ' + (ok ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200');
    }
    const reload = () => window.location.reload();

    // ── Categories ──
    document.getElementById('cat-add').addEventListener('click', async () => {
        const name = document.getElementById('cat-new').value.trim();
        if (!name) { feedback('cat-feedback', 'Type a category name.', false); return; }
        try { await req('POST', URLS.catStore, { name }); reload(); }
        catch (e) { feedback('cat-feedback', e.message, false); }
    });
    document.getElementById('cat-list').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-cat-row]'); if (!row) return;
        const id = row.dataset.catRow;
        try {
            if (e.target.matches('[data-cat-save]')) {
                await req('PUT', URLS.catUpdate.replace(':id', id), { name: row.querySelector('[data-cat-name]').value.trim() });
                feedback('cat-feedback', 'Category saved.', true);
            } else if (e.target.matches('[data-cat-toggle]')) {
                await req('PUT', URLS.catUpdate.replace(':id', id), { is_active: e.target.dataset.active !== '1' });
                reload();
            } else if (e.target.matches('[data-cat-delete]')) {
                if (e.target.dataset.armed !== '1') { e.target.dataset.armed = '1'; e.target.textContent = 'Confirm?'; setTimeout(() => { e.target.dataset.armed=''; e.target.textContent='Delete'; }, 3000); return; }
                await req('DELETE', URLS.catDestroy.replace(':id', id)); reload();
            } else if (e.target.closest('[data-cat-move]')) {
                const dir = e.target.closest('[data-cat-move]').dataset.catMove;
                const rows = Array.from(document.querySelectorAll('#cat-list [data-cat-row]'));
                const i = rows.indexOf(row), j = dir === 'up' ? i - 1 : i + 1;
                if (j < 0 || j >= rows.length) return;
                const ids = rows.map(r => Number(r.dataset.catRow));
                [ids[i], ids[j]] = [ids[j], ids[i]];
                await req('POST', URLS.catReorder, { ids }); reload();
            }
        } catch (err) { feedback('cat-feedback', err.message, false); }
    });

    // ── Items ──
    document.getElementById('item-add').addEventListener('click', async () => {
        const name = document.getElementById('item-new').value.trim();
        const catId = document.getElementById('item-new-cat').value;
        if (!name) { feedback('item-feedback', 'Type an item name.', false); return; }
        try { await req('POST', URLS.itemStore, { name, service_symptom_category_id: catId }); reload(); }
        catch (e) { feedback('item-feedback', e.message, false); }
    });
    document.getElementById('item-list').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-item-row]'); if (!row) return;
        const id = row.dataset.itemRow;
        try {
            if (e.target.matches('[data-item-save]')) {
                await req('PUT', URLS.itemUpdate.replace(':id', id), {
                    name: row.querySelector('[data-item-name]').value.trim(),
                    service_symptom_category_id: row.querySelector('[data-item-cat]').value,
                });
                feedback('item-feedback', 'Item saved.', true);
            } else if (e.target.matches('[data-item-toggle]')) {
                await req('PUT', URLS.itemUpdate.replace(':id', id), { is_active: e.target.dataset.active !== '1' });
                reload();
            } else if (e.target.matches('[data-item-delete]')) {
                if (e.target.dataset.armed !== '1') { e.target.dataset.armed = '1'; e.target.textContent = 'Confirm?'; setTimeout(() => { e.target.dataset.armed=''; e.target.textContent='Delete'; }, 3000); return; }
                await req('DELETE', URLS.itemDestroy.replace(':id', id)); reload();
            }
        } catch (err) { feedback('item-feedback', err.message, false); }
    });
})();
</script>
@endpush
