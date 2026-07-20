@props([
    'id',
    // 'fuel' | 'resolution' — the EXISTING preset categories (fuel-charge
    // notes vs closure/resolution notes). Deliberately not merged: the two
    // tables and their admin Settings page remain the source of truth.
    'type',
    'presets'  => null,
    // The textarea/input the chosen preset is appended into.
    'targetId',
])

@php $presets = $presets ?? collect(); @endphp

{{-- Billing Engine — the canonical preset-note dropdown + manager launcher
     (Billing Charge Operations polish). ONE adapter used by every charge
     workflow (New Fuel Charge, Collect Payment, Resolve, Mark
     Uncollectible, future Damage): selecting a preset appends it to the
     target field; the pencil opens the ONE shared Note Preset Manager
     modal. After any manager change, every dropdown of that category on
     the page refreshes in place — typed notes are never touched. --}}
<div class="mt-2 flex items-center gap-2">
    <select id="{{ $id }}" data-preset-type="{{ $type }}" data-preset-target="{{ $targetId }}"
            class="flex-1 min-w-0 border border-gray-200 rounded-md px-3 py-2 text-sm text-gray-600">
        <option value="">Insert preset note…</option>
        @foreach ($presets as $preset)
            <option value="{{ $preset->label }}">{{ $preset->label }}</option>
        @endforeach
    </select>
    <button type="button" data-preset-manage="{{ $type }}" title="Manage preset notes" aria-label="Manage preset notes"
            class="p-2 rounded-md border border-gray-200 text-teal-600 hover:bg-teal-50 transition shrink-0">
        <x-heroicon-o-pencil-square class="w-4 h-4" />
    </button>
</div>

@once
{{-- ── The ONE Note Preset Manager modal (above every action modal) ── --}}
<div id="bnp-manager-modal" class="hidden fixed inset-0 z-[10010] flex items-center justify-center bg-black/50 px-4 py-8 overflow-y-auto">
    <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-1">
            <h3 class="text-base font-semibold text-gray-900">Manage Preset Notes — <span id="bnp-type-label"></span></h3>
            <button type="button" id="bnp-close" class="text-gray-400 hover:text-gray-600">&times;</button>
        </div>
        <p class="text-xs text-gray-500 mb-4">Changes apply everywhere these presets are offered. Notes already typed into a form are never affected.</p>

        <div class="flex items-center gap-2 mb-3">
            <input type="text" id="bnp-new-label" maxlength="255" placeholder="New preset note…"
                   class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm">
            <button type="button" id="bnp-add"
                    class="px-3 py-2 text-sm rounded-md bg-teal-600 text-white hover:bg-teal-700 shrink-0">Add</button>
        </div>

        <div id="bnp-feedback" class="hidden mb-3 text-xs font-medium rounded-md px-3 py-2"></div>

        <div id="bnp-list" class="border border-gray-200 rounded-md divide-y divide-gray-100 max-h-72 overflow-y-auto">
            <div class="px-3 py-3 text-sm text-gray-400">Loading…</div>
        </div>

        <div class="flex justify-end mt-4">
            <button type="button" id="bnp-done" class="px-4 py-2 text-sm rounded-md border border-gray-300">Done</button>
        </div>
    </div>
</div>

<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const URLS = {
        list:    @json(route('admin.configurations.note-presets.list', ':type')),
        store:   @json(route('admin.configurations.note-presets.store', ':type')),
        update:  @json(route('admin.configurations.note-presets.update', ['type' => ':type', 'id' => ':id'])),
        destroy: @json(route('admin.configurations.note-presets.destroy', ['type' => ':type', 'id' => ':id'])),
        reorder: @json(route('admin.configurations.note-presets.reorder', ':type')),
    };
    const TYPE_LABELS = { fuel: 'Charge Notes', resolution: 'Resolution Notes' };

    const $ = (id) => document.getElementById(id);
    let currentType = null;
    let currentList = [];

    function esc(s) {
        return String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    }

    async function request(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(
                data.message || Object.values(data.errors || {}).flat().join(' ') || 'The request failed.'
            );
        }
        return data;
    }

    function feedback(message, ok) {
        const box = $('bnp-feedback');
        box.textContent = message;
        box.className = 'mb-3 text-xs font-medium rounded-md px-3 py-2 border '
            + (ok ? 'text-green-700 bg-green-50 border-green-200' : 'text-red-700 bg-red-50 border-red-200');
    }
    function clearFeedback() { $('bnp-feedback').classList.add('hidden'); $('bnp-feedback').className = 'hidden'; }

    // ── Live dropdown refresh — every select of this category, in place.
    // Only the <option> list is rebuilt; target fields are never touched.
    async function refreshDropdowns(type) {
        const data = await request('GET', URLS.list.replace(':type', type));
        currentList = data.presets || [];
        document.querySelectorAll(`select[data-preset-type="${type}"]`).forEach((sel) => {
            sel.innerHTML = '<option value="">Insert preset note…</option>'
                + currentList.map((p) => `<option value="${esc(p.label)}">${esc(p.label)}</option>`).join('');
        });
        return currentList;
    }

    function renderManagerList() {
        const list = $('bnp-list');
        if (!currentList.length) {
            list.innerHTML = '<div class="px-3 py-3 text-sm text-gray-400">No presets yet — add the first one above.</div>';
            return;
        }
        list.innerHTML = currentList.map((p, i) => `
            <div class="flex items-center gap-2 px-3 py-2" data-bnp-row="${p.id}">
                <div class="flex flex-col shrink-0">
                    <button type="button" data-bnp-move="up" title="Move up" class="text-gray-400 hover:text-gray-700 leading-none ${i === 0 ? 'invisible' : ''}">▲</button>
                    <button type="button" data-bnp-move="down" title="Move down" class="text-gray-400 hover:text-gray-700 leading-none ${i === currentList.length - 1 ? 'invisible' : ''}">▼</button>
                </div>
                <input type="text" value="${esc(p.label)}" maxlength="255"
                       class="flex-1 min-w-0 border border-gray-200 rounded-md px-2 py-1.5 text-sm" data-bnp-label>
                <button type="button" data-bnp-save title="Save changes"
                        class="px-2 py-1.5 text-xs rounded-md border border-gray-300 text-gray-600 hover:bg-gray-50 shrink-0">Save</button>
                <button type="button" data-bnp-delete title="Delete preset"
                        class="px-2 py-1.5 text-xs rounded-md border border-red-200 text-red-600 hover:bg-red-50 shrink-0">Delete</button>
            </div>`).join('');
    }

    async function openManager(type) {
        currentType = type;
        $('bnp-type-label').textContent = TYPE_LABELS[type] || type;
        $('bnp-new-label').value = '';
        clearFeedback();
        $('bnp-list').innerHTML = '<div class="px-3 py-3 text-sm text-gray-400">Loading…</div>';
        $('bnp-manager-modal').classList.remove('hidden');
        try {
            await refreshDropdowns(type);
            renderManagerList();
        } catch (e) {
            feedback(e.message, false);
        }
    }

    function closeManager() {
        $('bnp-manager-modal').classList.add('hidden');
        currentType = null;
    }
    $('bnp-close').addEventListener('click', closeManager);
    $('bnp-done').addEventListener('click', closeManager);
    $('bnp-manager-modal').addEventListener('click', (e) => {
        if (e.target === $('bnp-manager-modal')) closeManager();
    });

    // ── Add ──────────────────────────────────────────────────────────
    $('bnp-add').addEventListener('click', async () => {
        const label = $('bnp-new-label').value.trim();
        if (!label) { feedback('Type the preset text first.', false); return; }
        try {
            await request('POST', URLS.store.replace(':type', currentType), { label });
            $('bnp-new-label').value = '';
            await refreshDropdowns(currentType);
            renderManagerList();
            feedback('Preset added.', true);
        } catch (e) { feedback(e.message, false); }
    });

    // ── Row actions (save / delete / reorder) — delegated ────────────
    $('bnp-list').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-bnp-row]');
        if (!row) return;
        const id = row.dataset.bnpRow;

        if (e.target.matches('[data-bnp-save]')) {
            const label = row.querySelector('[data-bnp-label]').value.trim();
            if (!label) { feedback('The preset text cannot be empty.', false); return; }
            try {
                await request('PUT', URLS.update.replace(':type', currentType).replace(':id', id), { label });
                await refreshDropdowns(currentType);
                renderManagerList();
                feedback('Preset updated.', true);
            } catch (err) { feedback(err.message, false); }
        }

        if (e.target.matches('[data-bnp-delete]')) {
            // Two-step inline confirmation — first click arms, second deletes.
            if (e.target.dataset.armed !== '1') {
                e.target.dataset.armed = '1';
                e.target.textContent = 'Confirm?';
                setTimeout(() => { e.target.dataset.armed = ''; e.target.textContent = 'Delete'; }, 3000);
                return;
            }
            try {
                await request('DELETE', URLS.destroy.replace(':type', currentType).replace(':id', id));
                await refreshDropdowns(currentType);
                renderManagerList();
                feedback('Preset deleted.', true);
            } catch (err) { feedback(err.message, false); }
        }

        const move = e.target.closest('[data-bnp-move]');
        if (move) {
            const ids = currentList.map((p) => p.id);
            const idx = ids.indexOf(Number(id));
            const swap = move.dataset.bnpMove === 'up' ? idx - 1 : idx + 1;
            if (idx < 0 || swap < 0 || swap >= ids.length) return;
            [ids[idx], ids[swap]] = [ids[swap], ids[idx]];
            try {
                await request('POST', URLS.reorder.replace(':type', currentType), { ids });
                await refreshDropdowns(currentType);
                renderManagerList();
                feedback('Order saved.', true);
            } catch (err) { feedback(err.message, false); }
        }
    });

    // ── Page-wide delegation: preset insertion + manager launchers ───
    // Delegated, so selects rebuilt by refreshDropdowns keep working and
    // future modals participate automatically.
    document.addEventListener('change', (e) => {
        const sel = e.target.closest('select[data-preset-target]');
        if (!sel || !sel.value) return;
        const target = document.getElementById(sel.dataset.presetTarget);
        if (target) {
            target.value = target.value.trim()
                ? target.value.trim() + ' ' + sel.value
                : sel.value;
        }
        sel.value = '';
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-preset-manage]');
        if (btn) openManager(btn.dataset.presetManage);
    });

    window.BillingNotePresets = { openManager, refreshDropdowns };
})();
</script>
@endonce
