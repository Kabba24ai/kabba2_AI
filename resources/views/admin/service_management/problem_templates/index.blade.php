@extends('admin.layouts.app')

@section('title', 'Problem Library')

@push('css')
<style>
    /* Self-contained card styling (no asset rebuild needed on deploy). */
    .pl-card { display:flex; flex-direction:column; align-items:center; justify-content:flex-start; text-align:center;
        background:#fff; border:1px solid #e5e7eb; border-radius:0.85rem; padding:1.35rem 1rem 1.2rem;
        min-height:196px; cursor:pointer; position:relative; transition:box-shadow .15s ease, border-color .15s ease, transform .05s ease; }
    .pl-card:hover { box-shadow:0 6px 18px rgba(31,41,55,.08); border-color:#c7d2fe; }
    .pl-card:active { transform:translateY(1px); }
    .pl-card.pl-inactive { opacity:.55; }
    .pl-handle { position:absolute; top:.55rem; left:.55rem; color:#cbd5e1; cursor:grab; line-height:0; }
    .pl-handle:active { cursor:grabbing; }
    .pl-badge-inactive { position:absolute; top:.5rem; right:.5rem; font-size:.6rem; font-weight:700; text-transform:uppercase;
        letter-spacing:.04em; color:#9ca3af; background:#f3f4f6; border-radius:9999px; padding:2px 8px; }
    .pl-card-name { font-weight:700; letter-spacing:.03em; text-transform:uppercase; font-size:.8rem; color:#111827; margin-bottom:.85rem; margin-top:.35rem; }
    .pl-card-icon { color:#4f46e5; margin-bottom:.9rem; }
    .pl-card-stat { font-size:.8rem; color:#6b7280; line-height:1.55; }
    .pl-card-stat strong { color:#111827; font-weight:600; }
    .pl-add { border-style:dashed; border-color:#c7d2fe; background:#f8faff; align-items:center; justify-content:center; }
    .pl-add:hover { background:#eef2ff; }
    .pl-drag-ghost { opacity:.35; }
    .pl-item-row { display:flex; align-items:center; gap:.6rem; }
</style>
@endpush

@section('content')
    @include('flash::message')

    @php
        $iconFor = function (string $name): string {
            $n = strtolower($name);
            return match (true) {
                str_contains($n, 'engine') || str_contains($n, 'start')                        => 'heroicon-o-fire',
                str_contains($n, 'fuel')                                                        => 'heroicon-o-fire',
                str_contains($n, 'hydraul')                                                     => 'heroicon-o-adjustments-horizontal',
                str_contains($n, 'electric') || str_contains($n, 'charg') || str_contains($n, 'batter') => 'heroicon-o-bolt',
                str_contains($n, 'track') || str_contains($n, 'undercarriage')                  => 'heroicon-o-cog-6-tooth',
                str_contains($n, 'transmiss') || str_contains($n, 'drivetrain') || str_contains($n, 'drive') => 'heroicon-o-cog-8-tooth',
                str_contains($n, 'damage') || str_contains($n, 'physical') || str_contains($n, 'body') => 'heroicon-o-exclamation-triangle',
                str_contains($n, 'control')                                                     => 'heroicon-o-cursor-arrow-rays',
                str_contains($n, 'steer')                                                       => 'heroicon-o-arrow-path',
                str_contains($n, 'brake')                                                       => 'heroicon-o-hand-raised',
                str_contains($n, 'boom') || str_contains($n, 'lift')                            => 'heroicon-o-arrows-up-down',
                str_contains($n, 'attach')                                                      => 'heroicon-o-wrench',
                str_contains($n, 'field') || str_contains($n, 'recovery')                       => 'heroicon-o-lifebuoy',
                str_contains($n, 'safety')                                                      => 'heroicon-o-shield-check',
                str_contains($n, 'cool')                                                        => 'heroicon-o-sun',
                default                                                                         => 'heroicon-o-wrench-screwdriver',
            };
        };
        $plData = $categories->map(fn ($c) => [
            'id'        => $c->id,
            'name'      => $c->name,
            'universal' => $totalProfiles > 0 && (int) $c->profile_count === $totalProfiles,
            'profiles'  => (int) $c->profile_count,
            'items'     => $c->symptoms->map(fn ($s) => [
                'id' => $s->id, 'name' => $s->name, 'active' => (bool) $s->is_active,
            ])->values(),
        ])->values();
    @endphp

    <div class="max-w-7xl mx-auto px-4 py-6">

        <div class="flex items-start justify-between mb-6 gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Problem Library</h1>
                <p class="text-sm text-gray-500 mt-0.5">Manage shared problem categories and the symptoms used across Service intake.</p>
            </div>
            <div class="flex items-center gap-4 shrink-0 pt-1">
                <a href="{{ route('admin.service-management.problem-templates.equipment') }}" class="text-sm text-blue-600 hover:underline">Equipment Attachment</a>
                <a href="{{ route('admin.service-management.problem-templates.create') }}"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
                    <x-heroicon-o-plus class="w-4 h-4" /> New Template
                </a>
            </div>
        </div>

        {{-- ===== Category grid ===== --}}
        <div id="pl-grid-view">
            <p class="text-xs text-gray-400 mb-3">Card order is the category display order used across the Problem Library — drag a card by its handle to reorder.</p>
            <div id="pl-grid" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                @foreach ($categories as $cat)
                    <div class="pl-card {{ $cat->is_active ? '' : 'pl-inactive' }}" data-cat-id="{{ $cat->id }}">
                        <span class="pl-handle" title="Drag to reorder"><x-heroicon-o-bars-2 class="w-4 h-4" /></span>
                        @unless ($cat->is_active)<span class="pl-badge-inactive">Inactive</span>@endunless
                        <div class="pl-card-name">{{ $cat->name }}</div>
                        <div class="pl-card-icon"><x-dynamic-component :component="$iconFor($cat->name)" class="w-9 h-9" /></div>
                        <div class="pl-card-stat"><strong>{{ $cat->active_problem_count }}</strong> {{ $cat->active_problem_count === 1 ? 'Problem' : 'Problems' }}</div>
                        <div class="pl-card-stat">
                            @if ($totalProfiles > 0 && (int) $cat->profile_count === $totalProfiles)
                                All Equipment Profiles
                            @else
                                <strong>{{ $cat->profile_count }}</strong> Equipment {{ (int) $cat->profile_count === 1 ? 'Profile' : 'Profiles' }}
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Dedicated Add Category action card --}}
                <div id="pl-add-card" class="pl-card pl-add">
                    <x-heroicon-o-plus class="w-8 h-8 mb-2 text-indigo-500" />
                    <div class="pl-card-name" style="color:#4f46e5;">Add Category</div>
                </div>
            </div>
        </div>

        {{-- ===== Focused category workspace (in-page drill-down) ===== --}}
        <div id="pl-focus-view" class="hidden">
            <button type="button" id="pl-back" class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mb-4">
                <x-heroicon-o-arrow-left class="w-4 h-4" /> Back to Categories
            </button>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 max-w-3xl">
                <div class="flex items-center gap-3">
                    <span id="pl-focus-icon" class="text-indigo-600"></span>
                    <h2 id="pl-focus-name" class="text-lg font-bold uppercase tracking-wide text-gray-900"></h2>
                </div>
                <p id="pl-focus-stats" class="text-sm text-gray-500 mt-1 mb-5"></p>

                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Problem Items</p>
                <div id="pl-focus-items" class="space-y-1.5"></div>

                <form id="pl-add-item-form" class="mt-4 flex items-center gap-2">
                    <input type="text" id="pl-add-item-input" autocomplete="off" placeholder="Add a problem item…"
                        class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-400 outline-none">
                    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">Add</button>
                </form>

                <div class="mt-6 pt-4 border-t border-gray-100 flex justify-end">
                    <button type="button" id="pl-done" class="px-5 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">Done</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = @json(csrf_token());
    const CATS = @json($plData);
    const URLS = {
        reorderCat:  @json(route('admin.service-management.problem-templates.categories.reorder')),
        storeCat:    @json(route('admin.service-management.problem-templates.categories.store')),
        storeItem:   @json(route('admin.service-management.problem-templates.items.store')),
        itemTpl:     @json(route('admin.service-management.problem-templates.items.update', ['item' => '__ID__'])),
        reorderItem: @json(route('admin.service-management.problem-templates.items.reorder')),
    };
    const byId = {}; CATS.forEach(c => { byId[c.id] = c; });

    const $ = id => document.getElementById(id);
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }
    async function api(url, method, body) {
        const r = await fetch(url, { method, headers: { 'Content-Type':'application/json', 'Accept':'application/json', 'X-CSRF-TOKEN': CSRF }, body: body ? JSON.stringify(body) : undefined });
        const data = await r.json().catch(() => ({}));
        if (!r.ok || data.success === false) throw (data || {});
        return data;
    }

    const grid = $('pl-grid');
    const gridView = $('pl-grid-view'), focusView = $('pl-focus-view');
    let currentCat = null;

    // ── Category card drag-to-reorder → persists display order ──
    if (window.Sortable) {
        window.Sortable.create(grid, {
            handle: '.pl-handle', animation: 150, ghostClass: 'pl-drag-ghost',
            draggable: '.pl-card', filter: '#pl-add-card',
            onMove: evt => evt.related.id !== 'pl-add-card',
            onEnd: function () {
                const ids = Array.from(grid.querySelectorAll('.pl-card[data-cat-id]')).map(el => parseInt(el.dataset.catId, 10)).filter(Boolean);
                api(URLS.reorderCat, 'POST', { ids }).catch(() => {});
            },
        });
    }

    // ── Drill-down ──
    function openCategory(id, iconHtml) {
        const cat = byId[id]; if (!cat) return;
        currentCat = cat;
        $('pl-focus-icon').innerHTML = iconHtml || '';
        $('pl-focus-name').textContent = cat.name;
        updateStats(); renderItems();
        gridView.classList.add('hidden'); focusView.classList.remove('hidden');
        window.scrollTo(0, 0);
    }
    function backToGrid() { focusView.classList.add('hidden'); gridView.classList.remove('hidden'); currentCat = null; }

    function updateStats() {
        const active = currentCat.items.filter(i => i.active).length;
        const prof = currentCat.universal ? 'All Equipment Profiles'
            : currentCat.profiles + ' Equipment Profile' + (currentCat.profiles === 1 ? '' : 's');
        $('pl-focus-stats').textContent = active + ' active ' + (active === 1 ? 'problem' : 'problems') + ' · ' + prof;
    }
    function syncCard() {
        const card = grid.querySelector('.pl-card[data-cat-id="' + currentCat.id + '"]'); if (!card) return;
        const active = currentCat.items.filter(i => i.active).length;
        const strong = card.querySelector('.pl-card-stat strong');
        if (strong) { strong.textContent = active; if (strong.nextSibling) strong.nextSibling.textContent = ' ' + (active === 1 ? 'Problem' : 'Problems'); }
    }
    function renderItems() {
        const wrap = $('pl-focus-items'); wrap.innerHTML = '';
        if (!currentCat.items.length) { wrap.innerHTML = '<p class="text-sm text-gray-300 italic px-1">No problem items yet.</p>'; return; }
        currentCat.items.forEach(function (it) {
            const row = document.createElement('div');
            row.className = 'pl-item-row rounded-md border border-gray-100 bg-gray-50 px-3 py-2';
            row.dataset.itemId = it.id;
            row.innerHTML =
                '<span class="pl-item-handle cursor-grab text-gray-300 select-none" title="Drag to reorder">⠿</span>' +
                '<span class="pl-item-name flex-1 text-sm ' + (it.active ? 'text-gray-700' : 'text-gray-400 line-through') + '">' + esc(it.name) + '</span>' +
                '<button type="button" class="pl-item-toggle text-xs font-medium ' + (it.active ? 'text-gray-500' : 'text-green-600') + ' hover:underline">' + (it.active ? 'Deactivate' : 'Activate') + '</button>' +
                '<button type="button" class="pl-item-edit text-xs text-blue-600 hover:underline">Edit</button>' +
                '<button type="button" class="pl-item-del text-xs text-red-500 hover:underline">Delete</button>';
            wrap.appendChild(row);
        });
        if (window.Sortable) {
            window.Sortable.create(wrap, { handle: '.pl-item-handle', animation: 150, ghostClass: 'pl-drag-ghost', onEnd: function () {
                const ids = Array.from(wrap.querySelectorAll('[data-item-id]')).map(el => parseInt(el.dataset.itemId, 10));
                currentCat.items.sort((a, b) => ids.indexOf(a.id) - ids.indexOf(b.id));
                api(URLS.reorderItem, 'POST', { ids }).catch(() => {});
            } });
        }
    }

    $('pl-focus-items').addEventListener('click', async function (e) {
        const row = e.target.closest('[data-item-id]'); if (!row) return;
        const id = parseInt(row.dataset.itemId, 10);
        const item = currentCat.items.find(i => i.id === id); if (!item) return;
        const url = URLS.itemTpl.replace('__ID__', id);
        if (e.target.classList.contains('pl-item-toggle')) {
            try { await api(url, 'PUT', { is_active: !item.active }); item.active = !item.active; renderItems(); updateStats(); syncCard(); } catch (_) {}
        } else if (e.target.classList.contains('pl-item-edit')) {
            const name = prompt('Rename problem item:', item.name);
            if (name && name.trim() && name.trim() !== item.name) {
                try { await api(url, 'PUT', { name: name.trim() }); item.name = name.trim(); renderItems(); }
                catch (err) { alert((err && err.message) || 'Could not rename — the name may already exist.'); }
            }
        } else if (e.target.classList.contains('pl-item-del')) {
            if (confirm('Delete "' + item.name + '"? This removes it from the library.')) {
                try { await api(url, 'DELETE'); currentCat.items = currentCat.items.filter(i => i.id !== id); renderItems(); updateStats(); syncCard(); } catch (_) {}
            }
        }
    });

    $('pl-add-item-form').addEventListener('submit', async function (e) {
        e.preventDefault();
        const inp = $('pl-add-item-input'); const name = inp.value.trim(); if (!name) return;
        try {
            const res = await api(URLS.storeItem, 'POST', { service_symptom_category_id: currentCat.id, name });
            currentCat.items.push({ id: res.item.id, name: res.item.name, active: true });
            inp.value = ''; renderItems(); updateStats(); syncCard();
        } catch (err) { alert((err && err.message) || 'Could not add — the name may already exist.'); }
    });

    // ── Grid interactions ──
    grid.addEventListener('click', function (e) {
        if (e.target.closest('.pl-handle')) return;
        if (e.target.closest('#pl-add-card')) { startAddCategory(e.target.closest('#pl-add-card')); return; }
        const card = e.target.closest('.pl-card[data-cat-id]'); if (!card) return;
        const icon = card.querySelector('.pl-card-icon');
        openCategory(parseInt(card.dataset.catId, 10), icon ? icon.innerHTML : '');
    });
    $('pl-back').addEventListener('click', backToGrid);
    $('pl-done').addEventListener('click', backToGrid);

    function startAddCategory(card) {
        if (card.dataset.editing) return;
        card.dataset.editing = '1'; card.style.cursor = 'default';
        card.innerHTML =
            '<input type="text" id="pl-newcat" placeholder="Category name" class="w-full border border-gray-300 rounded-md px-2 py-1.5 text-sm mb-2 outline-none focus:border-blue-400">' +
            '<div class="flex gap-2"><button type="button" id="pl-newcat-save" class="px-3 py-1.5 rounded bg-blue-600 text-white text-xs font-semibold">Create</button>' +
            '<button type="button" id="pl-newcat-cancel" class="px-3 py-1.5 rounded border border-gray-300 text-xs">Cancel</button></div>';
        const inp = $('pl-newcat'); inp.focus();
        async function doCreate() {
            const name = inp.value.trim(); if (!name) return;
            try { await api(URLS.storeCat, 'POST', { name }); location.reload(); }
            catch (err) { alert((err && err.message) || 'Could not create — the name may already exist.'); }
        }
        $('pl-newcat-save').addEventListener('click', doCreate);
        $('pl-newcat-cancel').addEventListener('click', () => location.reload());
        inp.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); doCreate(); } });
    }
});
</script>
@endpush
