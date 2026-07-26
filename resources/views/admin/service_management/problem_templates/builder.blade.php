@extends('admin.layouts.app')

@section('title', $template ? 'Edit Problem Template' : 'Create Problem Template')

@push('css')
<style>
    .tpl-cols { display:grid; grid-template-columns:1fr; gap:1rem; align-items:start; }
    @media (min-width:1024px){ .tpl-cols{ grid-template-columns:minmax(0,25fr) minmax(0,30fr) minmax(0,45fr); } }
    .tpl-scroll { max-height:calc(100vh - 235px); overflow-y:auto; }
    .tpl-cat { display:flex; flex-direction:column; align-items:center; text-align:center; gap:.3rem; padding:.85rem .5rem;
        border:1px solid #e5e7eb; border-radius:.6rem; background:#fff; cursor:pointer; transition:border-color .12s ease, background .12s ease; }
    .tpl-cat:hover { border-color:#c7d2fe; background:#f8faff; }
    .tpl-cat.is-selected { border-color:#4f46e5; background:#eef2ff; box-shadow:inset 0 0 0 1px #4f46e5; }
    .tpl-cat-name { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.02em; color:#111827; line-height:1.2; }
    .tpl-cat-icon { color:#4f46e5; }
    .tpl-cat-meta { font-size:.66rem; color:#6b7280; line-height:1.35; }
    .tpl-avail-item, .tpl-tpl-item { display:flex; align-items:center; gap:.5rem; padding:.5rem .65rem; border:1px solid #e5e7eb;
        border-radius:.5rem; background:#fff; font-size:.83rem; color:#374151; }
    .tpl-avail-item { cursor:pointer; }
    .tpl-avail-item:hover { border-color:#c7d2fe; background:#f8faff; }
    .tpl-avail-item.is-added { opacity:.5; cursor:default; background:#f9fafb; }
    .tpl-avail-item.is-added:hover { border-color:#e5e7eb; background:#f9fafb; }
    .tpl-drag-ghost { opacity:.35; }
    .tpl-cat-heading { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.03em; color:#4f46e5; margin:.85rem 0 .45rem; }
    .tpl-cat-heading:first-child { margin-top:0; }
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
        $catData = $categories->map(fn ($c) => [
            'id'    => $c->id,
            'name'  => $c->name,
            'items' => $c->symptoms->map(fn ($s) => ['id' => $s->id, 'name' => $s->name])->values(),
        ])->values();
    @endphp

    <div class="max-w-7xl mx-auto px-4 pb-6">

        {{-- Sticky header + metadata + Save --}}
        <div class="sticky top-0 z-20 -mx-4 px-4 py-4 bg-white border-b border-gray-200 shadow-sm mb-5">
            @include('admin.service_management.problem_templates.partials._nav', ['active' => 'templates'])
            <div class="flex items-start justify-between gap-4 mb-3 mt-3">
                <div>
                    <h1 class="text-xl font-bold text-gray-900">{{ $template ? 'Edit Problem Template' : 'Create Problem Template' }}</h1>
                    <p class="text-sm text-gray-500 mt-0.5">Compose a reusable template from the Problem Library — categories and items stay in library order.</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <a href="{{ route('admin.service-management.problem-templates.templates') }}" class="text-sm text-gray-500 hover:text-gray-700 hover:underline">Cancel</a>
                    <span id="tpl-status" class="text-xs text-gray-400"></span>
                    <button type="button" id="tpl-save" class="inline-flex items-center gap-2 px-5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 disabled:opacity-40 transition">Save Template</button>
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-end">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Template Name <span class="text-red-500">*</span></label>
                    <input type="text" id="tpl-name" maxlength="255" value="{{ $template->name ?? '' }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm outline-none focus:border-blue-400" placeholder="e.g. Skid Steer">
                </div>
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Description</label>
                    <input type="text" id="tpl-desc" maxlength="255" value="{{ $template->description ?? '' }}"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm outline-none focus:border-blue-400" placeholder="Optional">
                </div>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700 pb-2 whitespace-nowrap">
                    <input type="checkbox" id="tpl-active" class="rounded text-blue-600 focus:ring-blue-500" @checked($template ? $template->is_active : true)>
                    Active
                </label>
            </div>
        </div>

        <div class="tpl-cols">
            {{-- LEFT — categories (library order, read-only) --}}
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Problem Categories</p>
                <div id="tpl-cats" class="tpl-scroll grid grid-cols-2 lg:grid-cols-1 gap-2 pr-1">
                    @foreach ($categories as $cat)
                        <div class="tpl-cat" data-cat-id="{{ $cat->id }}" role="button" tabindex="0">
                            <span class="tpl-cat-icon"><x-dynamic-component :component="$iconFor($cat->name)" class="w-6 h-6" /></span>
                            <span class="tpl-cat-name">{{ $cat->name }}</span>
                            <span class="tpl-cat-meta">{{ $cat->active_problem_count }} {{ $cat->active_problem_count === 1 ? 'problem' : 'problems' }}</span>
                            <span class="tpl-cat-meta">
                                @if ($totalProfiles > 0 && (int) $cat->profile_count === $totalProfiles)
                                    All Equipment Profiles
                                @else
                                    {{ $cat->profile_count }} {{ (int) $cat->profile_count === 1 ? 'profile' : 'profiles' }}
                                @endif
                            </span>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- CENTER — available items for the selected category --}}
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <div class="flex items-center justify-between gap-2 mb-3">
                    <h2 id="tpl-avail-title" class="text-sm font-semibold text-gray-800 truncate">Available Problems</h2>
                    <button type="button" id="tpl-add-all" class="text-xs font-semibold text-blue-600 hover:underline shrink-0">Add All</button>
                </div>
                <div id="tpl-avail-list" class="tpl-scroll space-y-1.5 pr-1"></div>
            </div>

            {{-- RIGHT — template composition (grouped, library order) --}}
            <div class="bg-white border border-gray-200 rounded-xl p-4">
                <h2 class="text-sm font-semibold text-gray-800 mb-3">Template <span id="tpl-count" class="text-gray-400 font-normal"></span></h2>
                <p id="tpl-empty" class="text-sm text-gray-400 py-10 text-center">Drag problem items here or use <span class="font-medium">Add All</span>.</p>
                <div id="tpl-list" class="tpl-scroll pr-1" style="min-height:6rem;"></div>
            </div>
        </div>
    </div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = @json(csrf_token());
    const CATS = @json($catData);
    const MODE = @json($template ? 'edit' : 'create');
    const SAVE_URL = @json($template
        ? route('admin.service-management.problem-templates.update', $template)
        : route('admin.service-management.problem-templates.store'));
    const SAVE_METHOD = @json($template ? 'PUT' : 'POST');
    const INDEX_URL = @json(route('admin.service-management.problem-templates.index'));

    const byId = {}; CATS.forEach(c => { byId[c.id] = c; });
    const selected = new Set((@json($selectedItemIds) || []).map(Number));
    let currentCatId = CATS.length ? CATS[0].id : null;

    const $ = id => document.getElementById(id);
    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;' }[c])); }

    // ── Left: category selection ──
    const catsWrap = $('tpl-cats');
    function selectCategory(id) {
        currentCatId = id;
        catsWrap.querySelectorAll('.tpl-cat').forEach(el => el.classList.toggle('is-selected', parseInt(el.dataset.catId, 10) === id));
        renderAvailable();
    }
    catsWrap.addEventListener('click', function (e) {
        const card = e.target.closest('.tpl-cat'); if (!card) return;
        selectCategory(parseInt(card.dataset.catId, 10));
    });

    // ── Center: available items ──
    function renderAvailable() {
        const cat = byId[currentCatId];
        $('tpl-avail-title').textContent = cat ? cat.name : 'Available Problems';
        const list = $('tpl-avail-list'); list.innerHTML = '';
        if (!cat) return;
        if (!cat.items.length) { list.innerHTML = '<p class="text-sm text-gray-300 italic px-1">No problems in this category.</p>'; return; }
        cat.items.forEach(function (it) {
            const added = selected.has(it.id);
            const row = document.createElement('div');
            row.className = 'tpl-avail-item' + (added ? ' is-added' : '');
            row.dataset.id = it.id;
            row.innerHTML = (added ? '<span class="text-green-500 font-bold">✓</span>' : '<span class="text-blue-500 font-bold">+</span>')
                + '<span class="flex-1 truncate">' + esc(it.name) + '</span>'
                + (added ? '<span class="text-xs text-gray-400">Added</span>' : '');
            list.appendChild(row);
        });
    }
    $('tpl-avail-list').addEventListener('click', function (e) {
        const row = e.target.closest('.tpl-avail-item'); if (!row || row.classList.contains('is-added')) return;
        addItem(parseInt(row.dataset.id, 10));
    });
    $('tpl-add-all').addEventListener('click', function () {
        const cat = byId[currentCatId]; if (!cat) return;
        cat.items.forEach(i => selected.add(i.id));
        renderAvailable(); renderTemplate();
    });

    // ── Right: template composition (grouped, canonical order) ──
    function renderTemplate() {
        const wrap = $('tpl-list'); wrap.innerHTML = '';
        $('tpl-empty').classList.toggle('hidden', selected.size > 0);
        $('tpl-count').textContent = selected.size ? '(' + selected.size + ')' : '';
        CATS.forEach(function (cat) {
            const items = cat.items.filter(i => selected.has(i.id));
            if (!items.length) return;
            const section = document.createElement('div');
            let html = '<p class="tpl-cat-heading">' + esc(cat.name) + '</p>';
            items.forEach(function (it) {
                html += '<div class="tpl-tpl-item bg-gray-50 mb-1" data-id="' + it.id + '">'
                    + '<span class="flex-1 truncate text-gray-800">' + esc(it.name) + '</span>'
                    + '<button type="button" class="tpl-remove text-red-400 hover:text-red-600 font-bold shrink-0" title="Remove">&times;</button></div>';
            });
            section.innerHTML = html;
            wrap.appendChild(section);
        });
    }
    $('tpl-list').addEventListener('click', function (e) {
        if (!e.target.classList.contains('tpl-remove')) return;
        const row = e.target.closest('[data-id]'); if (!row) return;
        removeItem(parseInt(row.dataset.id, 10));
    });

    function addItem(id) { selected.add(id); renderAvailable(); renderTemplate(); markDirty(); }
    function removeItem(id) { selected.delete(id); renderAvailable(); renderTemplate(); markDirty(); }

    // ── Drag: available → template (membership only; re-renders in library order) ──
    if (window.Sortable) {
        window.Sortable.create($('tpl-avail-list'), { group: { name: 'tpl', pull: 'clone', put: false }, sort: false, filter: '.is-added', ghostClass: 'tpl-drag-ghost' });
        window.Sortable.create($('tpl-list'), { group: { name: 'tpl', pull: false, put: true }, sort: false, ghostClass: 'tpl-drag-ghost',
            onAdd: function (evt) { const id = parseInt(evt.item.dataset.id, 10); if (id) addItem(id); else renderTemplate(); } });
    }

    // ── Save ──
    let dirty = false;
    function markDirty() { dirty = true; $('tpl-status').textContent = 'Unsaved changes'; }
    $('tpl-save').addEventListener('click', async function () {
        const name = $('tpl-name').value.trim();
        if (!name) { $('tpl-name').focus(); $('tpl-status').textContent = 'Template name is required.'; return; }
        const item_ids = [];
        CATS.forEach(c => c.items.forEach(i => { if (selected.has(i.id)) item_ids.push(i.id); }));
        $('tpl-save').disabled = true; $('tpl-status').textContent = 'Saving…';
        try {
            const res = await fetch(SAVE_URL, {
                method: SAVE_METHOD,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({ name, description: $('tpl-desc').value.trim() || null, is_active: $('tpl-active').checked, item_ids }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || data.success === false) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Save failed.');
            dirty = false;
            window.location.href = data.redirect || INDEX_URL;
        } catch (e) {
            $('tpl-status').textContent = ''; $('tpl-save').disabled = false;
            if (window.notyf) notyf.error(e.message); else alert(e.message);
        }
    });
    window.addEventListener('beforeunload', function (e) { if (dirty) { e.preventDefault(); e.returnValue = ''; } });

    // Init — auto-select first category.
    if (currentCatId !== null) selectCategory(currentCatId);
    renderTemplate();
});
</script>
@endpush
