@extends('admin.layouts.app')

@section('title', 'Problem Templates')

@section('content')
    @include('flash::message')

    <div class="max-w-5xl mx-auto px-4 py-6">
        <div class="flex items-center justify-between mb-5">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Problem Templates</h1>
                <p class="text-sm text-gray-500 mt-0.5">Reusable, ordered sets of reported problems, attached to equipment units to drive service-ticket intake.</p>
            </div>
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.service-management.problem-templates.library') }}" class="text-sm text-blue-600 hover:underline">Manage Library</a>
                <a href="{{ route('admin.service-management.problem-templates.equipment') }}" class="text-sm text-blue-600 hover:underline">Equipment Attachment</a>
                <button type="button" id="tpl-new-btn" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
                    <x-heroicon-o-plus class="w-4 h-4" /> New Template
                </button>
            </div>
        </div>

        <div class="bg-white border border-gray-200 rounded-xl divide-y divide-gray-100">
            @forelse ($templates as $template)
                <div class="flex items-center justify-between gap-4 px-5 py-4" data-tpl-row="{{ $template->id }}">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-semibold text-gray-900">{{ $template->name }}</span>
                            @unless ($template->is_active)
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500 border border-gray-200">Inactive</span>
                            @endunless
                        </div>
                        @if ($template->description)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $template->description }}</p>
                        @endif
                        <p class="text-xs text-gray-400 mt-0.5">{{ $template->item_count }} {{ Str::plural('item', $template->item_count) }} · attached to {{ $template->unit_count }} {{ Str::plural('unit', $template->unit_count) }}</p>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <a href="{{ route('admin.service-management.problem-templates.builder', $template) }}"
                           class="px-3 py-1.5 text-sm rounded-md border border-gray-300 text-gray-700 hover:bg-gray-50">Edit items</a>
                        <button type="button" data-tpl-toggle data-active="{{ $template->is_active ? '1' : '0' }}"
                                class="px-3 py-1.5 text-sm rounded-md border {{ $template->is_active ? 'border-gray-300 text-gray-600 hover:bg-gray-50' : 'border-green-300 text-green-700 hover:bg-green-50' }}">
                            {{ $template->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                        <button type="button" data-tpl-delete class="px-3 py-1.5 text-sm rounded-md border border-red-200 text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                </div>
            @empty
                <div class="px-5 py-12 text-center text-sm text-gray-400">No templates yet. Create one, then add items in the builder.</div>
            @endforelse
        </div>
    </div>

    {{-- New template modal --}}
    <div id="tpl-new-modal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 px-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">New Template</h3>
                <button type="button" id="tpl-new-close" class="text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Name <span class="text-red-500">*</span></label>
            <input type="text" id="tpl-name" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm mb-3" placeholder="e.g. Skid Steer">
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <input type="text" id="tpl-desc" maxlength="255" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm" placeholder="Optional">
            <div id="tpl-error" class="hidden mt-3 text-sm text-red-600"></div>
            <div class="flex justify-end gap-2 mt-4">
                <button type="button" id="tpl-cancel" class="px-4 py-2 text-sm rounded-md border border-gray-300">Cancel</button>
                <button type="button" id="tpl-create" class="px-4 py-2 text-sm rounded-md bg-blue-600 text-white hover:bg-blue-700">Create &amp; add items</button>
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
        store:   @json(route('admin.service-management.problem-templates.store')),
        update:  @json(route('admin.service-management.problem-templates.update', ':id')),
        destroy: @json(route('admin.service-management.problem-templates.destroy', ':id')),
    };
    const $ = (id) => document.getElementById(id);
    async function req(method, url, body) {
        const res = await fetch(url, { method, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }, body: body ? JSON.stringify(body) : undefined });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Request failed.');
        return data;
    }
    const modal = $('tpl-new-modal');
    const open = () => { $('tpl-name').value=''; $('tpl-desc').value=''; $('tpl-error').classList.add('hidden'); modal.classList.remove('hidden'); };
    const close = () => modal.classList.add('hidden');
    $('tpl-new-btn').addEventListener('click', open);
    $('tpl-new-close').addEventListener('click', close);
    $('tpl-cancel').addEventListener('click', close);
    modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

    $('tpl-create').addEventListener('click', async () => {
        const name = $('tpl-name').value.trim();
        if (!name) { $('tpl-error').textContent = 'Name is required.'; $('tpl-error').classList.remove('hidden'); return; }
        try {
            const data = await req('POST', URLS.store, { name, description: $('tpl-desc').value.trim() || null });
            window.location.href = data.builder_url;
        } catch (e) { $('tpl-error').textContent = e.message; $('tpl-error').classList.remove('hidden'); }
    });

    document.querySelector('.divide-y').addEventListener('click', async (e) => {
        const row = e.target.closest('[data-tpl-row]'); if (!row) return;
        const id = row.dataset.tplRow;
        try {
            if (e.target.matches('[data-tpl-toggle]')) {
                await req('PUT', URLS.update.replace(':id', id), { is_active: e.target.dataset.active !== '1' });
                window.location.reload();
            } else if (e.target.matches('[data-tpl-delete]')) {
                if (e.target.dataset.armed !== '1') { e.target.dataset.armed = '1'; e.target.textContent = 'Confirm?'; setTimeout(() => { e.target.dataset.armed=''; e.target.textContent='Delete'; }, 3000); return; }
                await req('DELETE', URLS.destroy.replace(':id', id)); window.location.reload();
            }
        } catch (err) { if (window.notyf) notyf.error(err.message); else alert(err.message); }
    });
})();
</script>
@endpush
