@extends('admin.layouts.app')

@section('title', 'Dispatch System Logic')

@section('content')

@php
    $statusColors = [
        'active'       => 'bg-green-100 text-green-700',
        'under_review' => 'bg-yellow-100 text-yellow-800',
        'deprecated'   => 'bg-gray-100 text-gray-500',
    ];
    $visibilityColors = [
        'internal_admin'   => 'bg-blue-100 text-blue-700',
        'customer_visible' => 'bg-purple-100 text-purple-700',
        'developer_only'   => 'bg-orange-100 text-orange-700',
    ];
@endphp

{{-- Page Header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-book-open class="w-6 h-6 text-indigo-600" />
            Dispatch System Logic
        </h1>
        <p class="mt-1 text-sm text-gray-500 max-w-2xl">
            This page documents the logic, assumptions, and decision rules used by the Dispatch module.
            These notes are for reference only and do not directly change system behavior.
        </p>
    </div>
    <button type="button" id="add-logic-btn"
        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm shrink-0">
        <x-heroicon-o-plus class="w-4 h-4" />
        Add Logic Note
    </button>
</div>

{{-- Filter bar --}}
<form method="GET" action="{{ route('admin.tutorials.system-logic.dispatch.index') }}"
      class="flex flex-wrap gap-3 mb-6 bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
    <div class="flex-1 min-w-[200px]">
        <input type="text" name="search" value="{{ request('search') }}"
               placeholder="Search title, summary, or logic…"
               class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
    </div>
    <div class="w-48">
        <select name="section" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Sections</option>
            @foreach ($sectionLabels as $key => $label)
                <option value="{{ $key }}" @selected(request('section') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="w-40">
        <select name="status" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">All Statuses</option>
            @foreach ($statuses as $s)
                <option value="{{ $s->value }}" @selected(request('status') === $s->value)>{{ $s->label() }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="px-4 py-2 bg-gray-700 text-white rounded-md text-sm font-medium hover:bg-gray-800">
        Filter
    </button>
    @if (request()->hasAny(['search', 'section', 'status']))
        <a href="{{ route('admin.tutorials.system-logic.dispatch.index') }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-600 rounded-md text-sm font-medium hover:bg-gray-50">
            Clear
        </a>
    @endif
</form>

{{-- Document Count --}}
<div class="flex items-center gap-2 mb-4 text-sm text-gray-500">
    <x-heroicon-o-document-text class="w-4 h-4" />
    <span>{{ $documents->count() }} {{ Str::plural('note', $documents->count()) }}</span>
</div>

{{-- Documents Grid --}}
@if ($documents->isEmpty())
    <div class="bg-white border border-dashed border-gray-300 rounded-xl p-12 text-center text-gray-400">
        <x-heroicon-o-document-text class="w-10 h-10 mx-auto mb-3 text-gray-300" />
        <p class="text-base font-medium">No logic notes found.</p>
        <p class="text-sm mt-1">Add your first note using the button above.</p>
    </div>
@else
    {{-- Group by section --}}
    @php
        $grouped = $documents->groupBy('section_key');
    @endphp

    <div class="space-y-8">
        @foreach ($grouped as $sectionKey => $sectionDocs)
            <div>
                {{-- Section heading --}}
                <h2 class="text-xs font-bold uppercase tracking-widest text-indigo-600 mb-3 flex items-center gap-2">
                    <x-heroicon-o-tag class="w-3.5 h-3.5" />
                    {{ $sectionLabels[$sectionKey] ?? ucwords(str_replace('_', ' ', $sectionKey)) }}
                    <span class="ml-1 text-gray-400 font-normal normal-case tracking-normal">{{ $sectionDocs->count() }}</span>
                </h2>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                    @foreach ($sectionDocs as $doc)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm p-5 flex flex-col gap-3
                            {{ $doc->status->value === 'deprecated' ? 'opacity-60' : '' }}"
                            id="logic-doc-{{ $doc->id }}">

                            {{-- Card header --}}
                            <div class="flex items-start justify-between gap-3">
                                <h3 class="font-semibold text-gray-900 text-sm leading-snug flex-1
                                    {{ $doc->status->value === 'deprecated' ? 'line-through text-gray-400' : '' }}">
                                    {{ $doc->title }}
                                </h3>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button"
                                        class="edit-logic-btn text-gray-400 hover:text-indigo-600 transition-colors"
                                        title="Edit"
                                        data-id="{{ $doc->id }}"
                                        data-section="{{ $doc->section_key }}"
                                        data-title="{{ $doc->title }}"
                                        data-summary="{{ $doc->summary }}"
                                        data-logic="{{ $doc->logic_body }}"
                                        data-status="{{ $doc->status->value }}"
                                        data-visibility="{{ $doc->visibility->value }}"
                                        data-sort="{{ $doc->sort_order }}">
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                    </button>
                                    <button type="button"
                                        class="delete-logic-btn text-gray-400 hover:text-red-600 transition-colors"
                                        title="Delete"
                                        data-id="{{ $doc->id }}"
                                        data-title="{{ $doc->title }}">
                                        <x-heroicon-o-trash class="w-4 h-4" />
                                    </button>
                                </div>
                            </div>

                            {{-- Badges --}}
                            <div class="flex flex-wrap gap-1.5">
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium {{ $statusColors[$doc->status->value] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $doc->status->label() }}
                                </span>
                                <span class="inline-block px-2 py-0.5 rounded-full text-[11px] font-medium {{ $visibilityColors[$doc->visibility->value] ?? 'bg-gray-100 text-gray-500' }}">
                                    {{ $doc->visibility->label() }}
                                </span>
                            </div>

                            {{-- Summary --}}
                            @if ($doc->summary)
                                <p class="text-sm text-gray-600 leading-relaxed">{{ $doc->summary }}</p>
                            @endif

                            {{-- Logic Body (collapsible) --}}
                            @if ($doc->logic_body)
                                <details class="group">
                                    <summary class="text-xs text-indigo-600 cursor-pointer font-medium hover:text-indigo-800 list-none flex items-center gap-1">
                                        <x-heroicon-o-chevron-right class="w-3.5 h-3.5 group-open:rotate-90 transition-transform" />
                                        View full logic
                                    </summary>
                                    <div class="mt-2 text-sm text-gray-700 whitespace-pre-wrap bg-gray-50 rounded-lg p-3 border border-gray-100 leading-relaxed">{{ $doc->logic_body }}</div>
                                </details>
                            @endif

                            {{-- Footer: meta --}}
                            <div class="pt-2 border-t border-gray-100 text-[11px] text-gray-400 flex items-center gap-3">
                                @if ($doc->updater || $doc->creator)
                                    <span>
                                        {{ $doc->updater ? 'Updated by ' . ($doc->updater->name ?? $doc->updater->first_name) : 'Created by ' . ($doc->creator->name ?? $doc->creator->first_name) }}
                                    </span>
                                    <span>·</span>
                                @endif
                                <span>{{ $doc->updated_at->format('M j, Y') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
@endif


{{-- ===== Add / Edit Modal ===== --}}
<div id="logic-modal" class="fixed inset-0 z-50 hidden" aria-modal="true">
    {{-- Backdrop --}}
    <div id="logic-modal-backdrop" class="absolute inset-0 bg-gray-900/50"></div>

    {{-- Panel --}}
    <div class="absolute inset-y-0 right-0 w-full max-w-xl bg-white shadow-xl flex flex-col">
        {{-- Header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b">
            <h2 class="text-base font-semibold text-gray-900" id="logic-modal-title">Add Logic Note</h2>
            <button type="button" id="logic-modal-close" class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Form --}}
        <form id="logic-form" class="flex-1 overflow-y-auto p-6 space-y-4">
            <input type="hidden" id="logic-doc-id" value="">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Section / Category <span class="text-red-500">*</span></label>
                <select id="f-section" name="section_key" required
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Select a section…</option>
                    @foreach ($sectionLabels as $key => $label)
                        <option value="{{ $key }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Title <span class="text-red-500">*</span></label>
                <input type="text" id="f-title" name="title" required maxlength="255"
                       placeholder="e.g. Driver CDL Assignment Logic"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Summary</label>
                <textarea id="f-summary" name="summary" rows="3" maxlength="1000"
                          placeholder="One or two sentences describing the rule…"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Full Logic Body</label>
                <textarea id="f-logic-body" name="logic_body" rows="8"
                          placeholder="Describe the complete decision logic, assumptions, constraints…"
                          class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500 resize-y font-mono"></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Status <span class="text-red-500">*</span></label>
                    <select id="f-status" name="status" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($statuses as $s)
                            <option value="{{ $s->value }}">{{ $s->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Visibility <span class="text-red-500">*</span></label>
                    <select id="f-visibility" name="visibility" required
                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @foreach ($visibilities as $v)
                            <option value="{{ $v->value }}">{{ $v->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="w-28">
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort Order</label>
                <input type="number" id="f-sort" name="sort_order" min="0" value="0"
                       class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <div id="logic-form-error" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2"></div>
        </form>

        {{-- Footer --}}
        <div class="px-6 py-4 border-t flex items-center justify-between gap-3">
            <button type="button" id="logic-modal-cancel"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                Cancel
            </button>
            <button type="button" id="logic-save-btn"
                    class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-md">
                Save Note
            </button>
        </div>
    </div>
</div>

{{-- ===== Delete Confirm Modal ===== --}}
<div id="delete-modal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="absolute inset-0 bg-gray-900/50"></div>
    <div class="relative bg-white rounded-xl shadow-xl p-6 max-w-sm w-full mx-4">
        <h3 class="text-base font-semibold text-gray-900 mb-2">Delete Logic Note?</h3>
        <p class="text-sm text-gray-600 mb-5">
            "<span id="delete-doc-title" class="font-medium"></span>" will be soft-deleted and can be restored from the database if needed.
        </p>
        <div class="flex gap-3 justify-end">
            <button type="button" id="delete-cancel"
                    class="px-4 py-2 text-sm text-gray-600 border border-gray-300 rounded-md hover:bg-gray-50">
                Cancel
            </button>
            <button type="button" id="delete-confirm"
                    class="px-4 py-2 text-sm bg-red-600 hover:bg-red-700 text-white rounded-md font-medium">
                Delete
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
(function () {
    const modal        = document.getElementById('logic-modal');
    const modalTitle   = document.getElementById('logic-modal-title');
    const modalClose   = document.getElementById('logic-modal-close');
    const modalCancel  = document.getElementById('logic-modal-cancel');
    const backdrop     = document.getElementById('logic-modal-backdrop');
    const saveBtn      = document.getElementById('logic-save-btn');
    const formError    = document.getElementById('logic-form-error');
    const docIdInput   = document.getElementById('logic-doc-id');

    const fSection    = document.getElementById('f-section');
    const fTitle      = document.getElementById('f-title');
    const fSummary    = document.getElementById('f-summary');
    const fLogicBody  = document.getElementById('f-logic-body');
    const fStatus     = document.getElementById('f-status');
    const fVisibility = document.getElementById('f-visibility');
    const fSort       = document.getElementById('f-sort');

    const deleteModal  = document.getElementById('delete-modal');
    const deleteTitleEl= document.getElementById('delete-doc-title');
    const deleteCancel = document.getElementById('delete-cancel');
    const deleteConfirm= document.getElementById('delete-confirm');
    let   pendingDeleteId = null;

    function openModal(mode, data = {}) {
        modalTitle.textContent = mode === 'edit' ? 'Edit Logic Note' : 'Add Logic Note';
        docIdInput.value       = data.id ?? '';
        fSection.value         = data.section ?? '';
        fTitle.value           = data.title ?? '';
        fSummary.value         = data.summary ?? '';
        fLogicBody.value       = data.logic ?? '';
        fStatus.value          = data.status ?? 'active';
        fVisibility.value      = data.visibility ?? 'internal_admin';
        fSort.value            = data.sort ?? 0;
        formError.classList.add('hidden');
        formError.textContent = '';
        modal.classList.remove('hidden');
        fTitle.focus();
    }

    function closeModal() {
        modal.classList.add('hidden');
    }

    // Open modal for new note
    document.getElementById('add-logic-btn').addEventListener('click', () => openModal('add'));
    modalClose.addEventListener('click', closeModal);
    modalCancel.addEventListener('click', closeModal);
    backdrop.addEventListener('click', closeModal);

    // Open modal for edit
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.edit-logic-btn');
        if (!btn) return;
        openModal('edit', {
            id:         btn.dataset.id,
            section:    btn.dataset.section,
            title:      btn.dataset.title,
            summary:    btn.dataset.summary,
            logic:      btn.dataset.logic,
            status:     btn.dataset.status,
            visibility: btn.dataset.visibility,
            sort:       btn.dataset.sort,
        });
    });

    // Save
    saveBtn.addEventListener('click', async function () {
        formError.classList.add('hidden');
        const id = docIdInput.value;
        const method = id ? 'PUT' : 'POST';
        const url = id
            ? `/admin/tutorials/system-logic/dispatch/${id}`
            : '/admin/tutorials/system-logic/dispatch';

        const body = new URLSearchParams({
            section_key:  fSection.value,
            title:        fTitle.value,
            summary:      fSummary.value,
            logic_body:   fLogicBody.value,
            status:       fStatus.value,
            visibility:   fVisibility.value,
            sort_order:   fSort.value,
            _token:       document.querySelector('meta[name=csrf-token]')?.content ?? '',
            _method:      method === 'PUT' ? 'PUT' : undefined,
        });

        if (method === 'PUT') body.set('_method', 'PUT');

        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving…';

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body,
            });
            const json = await res.json();
            if (!res.ok || !json.success) {
                let msg = json.message ?? 'Validation failed.';
                if (json.errors) {
                    msg = Object.values(json.errors).flat().join(' ');
                }
                formError.textContent = msg;
                formError.classList.remove('hidden');
            } else {
                closeModal();
                window.location.reload();
            }
        } catch {
            formError.textContent = 'Network error. Please try again.';
            formError.classList.remove('hidden');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Save Note';
        }
    });

    // Delete: open confirm
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.delete-logic-btn');
        if (!btn) return;
        pendingDeleteId = btn.dataset.id;
        deleteTitleEl.textContent = btn.dataset.title;
        deleteModal.classList.remove('hidden');
    });

    deleteCancel.addEventListener('click', () => {
        deleteModal.classList.add('hidden');
        pendingDeleteId = null;
    });

    deleteConfirm.addEventListener('click', async function () {
        if (!pendingDeleteId) return;
        deleteConfirm.disabled = true;
        deleteConfirm.textContent = 'Deleting…';

        try {
            const res = await fetch(`/admin/tutorials/system-logic/dispatch/${pendingDeleteId}`, {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams({
                    _method: 'DELETE',
                    _token:  document.querySelector('meta[name=csrf-token]')?.content ?? '',
                }),
            });
            const json = await res.json();
            if (json.success) {
                document.getElementById(`logic-doc-${pendingDeleteId}`)?.remove();
                deleteModal.classList.add('hidden');
            }
        } catch {
            /* silent */
        } finally {
            deleteConfirm.disabled = false;
            deleteConfirm.textContent = 'Delete';
            pendingDeleteId = null;
        }
    });
})();
</script>
@endpush
