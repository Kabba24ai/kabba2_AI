@extends('admin.layouts.app')

@section('title', 'Note Presets')

@section('content')

    @include('flash::message')

    {{-- Billing Engine UI refinement — managed preset notes. The Billing
         Engine dropdowns (New Fuel Charge notes, Resolve notes) load from
         these lists; nothing is hardcoded in Blade anymore. --}}
    <div class="max-w-4xl mx-auto px-4 py-6">
        <div class="mb-6">
            <h1 class="text-xl font-bold text-gray-900">Note Presets</h1>
            <p class="text-sm text-gray-500 mt-0.5">Manage the canned notes offered in Billing Engine dropdowns. Lower order shows first.</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            @foreach ([
                ['type' => 'fuel', 'title' => 'Fuel Charge Notes', 'presets' => $fuelPresets],
                ['type' => 'resolution', 'title' => 'Resolution Notes', 'presets' => $resolutionPresets],
            ] as $section)
                <div class="bg-white border border-gray-200 rounded-xl p-5" data-preset-section="{{ $section['type'] }}">
                    <h2 class="text-base font-semibold text-gray-900 mb-4">{{ $section['title'] }}</h2>

                    <ul class="space-y-2 mb-4" data-preset-list>
                        @foreach ($section['presets'] as $preset)
                            <li class="flex items-center gap-2 border border-gray-200 rounded-lg px-3 py-2"
                                data-preset-id="{{ $preset->id }}">
                                <input type="number" min="0" value="{{ $preset->sort_order }}"
                                       class="w-16 border border-gray-200 rounded px-2 py-1 text-xs text-center" data-preset-order
                                       title="Display order">
                                <input type="text" value="{{ $preset->label }}"
                                       class="flex-1 border border-gray-200 rounded px-2 py-1 text-sm" data-preset-label>
                                <button type="button" data-preset-save
                                        class="px-2 py-1 text-xs rounded border border-blue-200 text-blue-600 hover:bg-blue-50">Save</button>
                                <button type="button" data-preset-delete
                                        class="px-2 py-1 text-xs rounded border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                            </li>
                        @endforeach
                    </ul>

                    <div class="flex items-center gap-2">
                        <input type="text" placeholder="New preset…" data-preset-new-label
                               class="flex-1 border border-gray-300 rounded-md px-3 py-2 text-sm">
                        <button type="button" data-preset-add
                                class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Add</button>
                    </div>
                    <p class="hidden text-xs text-red-600 mt-2" data-preset-error></p>
                </div>
            @endforeach
        </div>
    </div>

@endsection

@push('js')
<script>
(function () {
    'use strict';

    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    const BASE = @json(route('admin.configurations.note-presets.index'));

    async function call(method, url, body) {
        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: body ? JSON.stringify(body) : undefined,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) {
            throw new Error(Object.values(data.errors || {}).flat().join(' ') || data.message || 'Request failed.');
        }
        return data;
    }

    document.querySelectorAll('[data-preset-section]').forEach((section) => {
        const type = section.dataset.presetSection;
        const err = section.querySelector('[data-preset-error]');

        const showError = (m) => { err.textContent = m; err.classList.remove('hidden'); };
        const clearError = () => err.classList.add('hidden');

        section.querySelector('[data-preset-add]').addEventListener('click', async () => {
            clearError();
            const input = section.querySelector('[data-preset-new-label]');
            const label = input.value.trim();
            if (!label) return;
            try {
                await call('POST', `${BASE}/${type}`, { label });
                window.location.reload();
            } catch (e) { showError(e.message); }
        });

        section.addEventListener('click', async (e) => {
            const row = e.target.closest('[data-preset-id]');
            if (!row) return;
            const id = row.dataset.presetId;

            if (e.target.matches('[data-preset-save]')) {
                clearError();
                try {
                    await call('PUT', `${BASE}/${type}/${id}`, {
                        label: row.querySelector('[data-preset-label]').value.trim(),
                        sort_order: Number(row.querySelector('[data-preset-order]').value) || 0,
                    });
                    e.target.textContent = 'Saved';
                    setTimeout(() => { e.target.textContent = 'Save'; }, 1200);
                } catch (er) { showError(er.message); }
            }

            if (e.target.matches('[data-preset-delete]')) {
                clearError();
                if (!confirm('Delete this preset?')) return;
                try {
                    await call('DELETE', `${BASE}/${type}/${id}`);
                    row.remove();
                } catch (er) { showError(er.message); }
            }
        });
    });
})();
</script>
@endpush
