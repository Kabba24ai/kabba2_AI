@extends('admin.layouts.app')

@section('title', 'Key Comparisons')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-6 py-5">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900">Key Comparisons</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage matching criteria libraries by equipment category</p>
                </div>
                {{-- <button id="kc-open-form" type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Criterion
                </button> --}}
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">
            <aside class="border-r border-gray-200 bg-gray-50/60 p-4">
                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Equipment Categories</p>
                <div id="kc-category-list" class="space-y-1.5"></div>
            </aside>

            <section class="p-5">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 id="kc-active-category" class="text-lg font-semibold text-gray-900">Category</h2>
                        <p class="text-xs text-gray-500">Matching criteria library applied to this category.</p>
                    </div>
                    <div class="inline-flex rounded-lg border border-gray-200 bg-white p-1">
                        <button type="button" data-kc-tab="property" class="kc-tab-btn rounded-md px-3 py-1.5 text-sm font-semibold text-gray-600">Property</button>
                        <button type="button" data-kc-tab="ai-table" class="kc-tab-btn rounded-md px-3 py-1.5 text-sm font-semibold text-gray-600">AI Table</button>
                    </div>
                </div>

                <div id="kc-criteria-panel">
                    <div id="kc-criteria-list" class="space-y-3"></div>
                </div>

                <div id="kc-ai-panel" class="hidden">
                    <div id="kc-ai-list"></div>
                </div>
            </section>
        </div>
    </div>
</div>

<div id="kc-modal" class="fixed inset-0 z-[9999] hidden">
    <div id="kc-modal-overlay" class="absolute inset-0 bg-gray-900/40"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-xl rounded-xl border border-gray-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 id="kc-modal-title" class="text-base font-semibold text-gray-900">Add Criterion</h3>
                <button type="button" id="kc-modal-close" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                        <input id="kc-name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="e.g. Lifting Capacity">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Unit</label>
                        <input id="kc-unit" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none" placeholder="e.g. lbs">
                    </div>
                </div>

                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Default Weight</label>
                    <input id="kc-weight" type="range" min="0" max="100" value="50" class="w-full accent-emerald-500">
                    <p class="mt-1 text-xs text-gray-500">Weight: <span id="kc-weight-label">50</span></p>
                </div>

                <!-- Row 1 -->
                <div class="grid gap-2 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-upgrade" type="checkbox" data-row="1" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-row-exclusive">
                        Upgrade exceeds value
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-caution-below" type="checkbox" data-row="1" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-row-exclusive">
                        Caution if below value
                    </label>
                </div>

                <!-- Row 2 -->
                <div class="grid gap-2 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-upgrade-below" type="checkbox" data-row="2" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-row-exclusive">
                        Upgrade is below value
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-caution" type="checkbox" data-row="2" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-row-exclusive">
                        Caution if exceeds value
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                <button id="kc-modal-cancel" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button id="kc-modal-save" type="button" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Save</button>
            </div>
        </div>
    </div>
</div>

<div id="kc-ai-spec-modal" class="fixed inset-0 z-[9999] hidden">
    <div id="kc-ai-spec-modal-overlay" class="absolute inset-0 bg-gray-900/40"></div>
    <div class="relative z-10 flex min-h-full items-center justify-center p-4">
        <div class="w-full max-w-lg rounded-xl border border-gray-200 bg-white shadow-xl">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <h3 class="text-base font-semibold text-gray-900">Create Specification</h3>
                <button type="button" id="kc-ai-spec-modal-close" class="rounded p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div class="space-y-4 px-5 py-4">
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Name</label>
                    <input id="kc-ai-spec-name" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none" placeholder="e.g. Engine Type">
                </div>
                <div>
                    <label class="mb-1 block text-xs font-semibold text-gray-600">Unit</label>
                    <input id="kc-ai-spec-unit" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-teal-500 focus:outline-none" placeholder="e.g. cc, hp">
                </div>
            </div>

            <div class="flex justify-end gap-2 border-t border-gray-100 px-5 py-4">
                <button id="kc-ai-spec-modal-cancel" type="button" class="rounded-lg px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-100">Cancel</button>
                <button id="kc-ai-spec-modal-save" type="button" class="rounded-lg bg-teal-600 px-5 py-2 text-sm font-semibold text-white hover:bg-teal-700">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const categories = @json($categories ?? []);
    let selectedCategoryId = Number(@json($activeCategoryId ?? 0));
    let criteria = @json($initialCriteria ?? []);
    let aiSpecs = @json($initialAiSpecs ?? []);
    let editingId = null;
    let activeTab = 'property';

    const categoryListEl = document.getElementById('kc-category-list');
    const criteriaListEl = document.getElementById('kc-criteria-list');
    const activeCategoryEl = document.getElementById('kc-active-category');
    const openFormBtn = document.getElementById('kc-open-form');
    const criteriaPanelEl = document.getElementById('kc-criteria-panel');
    const aiPanelEl = document.getElementById('kc-ai-panel');
    const aiListEl = document.getElementById('kc-ai-list');
    const tabButtons = Array.from(document.querySelectorAll('.kc-tab-btn'));

    const modal = document.getElementById('kc-modal');
    const modalOverlay = document.getElementById('kc-modal-overlay');
    const modalClose = document.getElementById('kc-modal-close');
    const modalCancel = document.getElementById('kc-modal-cancel');
    const modalSave = document.getElementById('kc-modal-save');
    const modalTitle = document.getElementById('kc-modal-title');

    const aiSpecModal = document.getElementById('kc-ai-spec-modal');
    const aiSpecModalOverlay = document.getElementById('kc-ai-spec-modal-overlay');
    const aiSpecModalClose = document.getElementById('kc-ai-spec-modal-close');
    const aiSpecModalCancel = document.getElementById('kc-ai-spec-modal-cancel');
    const aiSpecModalSave = document.getElementById('kc-ai-spec-modal-save');
    const aiSpecNameInput = document.getElementById('kc-ai-spec-name');
    const aiSpecUnitInput = document.getElementById('kc-ai-spec-unit');

    const nameInput = document.getElementById('kc-name');
    const unitInput = document.getElementById('kc-unit');
    const weightInput = document.getElementById('kc-weight');
    const weightLabel = document.getElementById('kc-weight-label');
    const upgradeInput = document.getElementById('kc-upgrade');
    const cautionInput = document.getElementById('kc-caution');
    const upgradeBelowInput = document.getElementById('kc-upgrade-below');
    const cautionBelowInput = document.getElementById('kc-caution-below');

    const LIST_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.index'));
    const ADD_URL_BASE = @json(route('admin.maintenance-management.key-comparisons.criteria.add', ['criteria_id' => '__ID__']));
    const STORE_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.store'));
    const AI_SPEC_STORE_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.ai-specifications.store'));
    const UPDATE_BASE_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.index'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let isLoadingCriteria = false;

    // Handle row-exclusive checkbox logic:
    // If any checkbox in row 1 is checked, uncheck all in row 2
    // If any checkbox in row 2 is checked, uncheck all in row 1
    document.addEventListener('change', (event) => {
        if (!event.target.classList.contains('checkbox-row-exclusive')) return;

        const row = event.target.dataset.row;
        if (!row) return;

        const currentRow = Number(row);
        const otherRow = currentRow === 1 ? 2 : 1;

        // If this checkbox is being checked, uncheck all in the other row
        if (event.target.checked) {
            document.querySelectorAll(`[data-row="${otherRow}"].checkbox-row-exclusive`).forEach((checkbox) => {
                checkbox.checked = false;
            });
        }
    });

    function esc(value) {
        return String(value || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#039;');
    }

    function activeCategory() {
        return categories.find((category) => Number(category.id) === Number(selectedCategoryId));
    }

    function renderCheckedConditions(item) {
        const checked = [];

        if (item.upgrade_exceeds_value) checked.push('Upgrade exceeds value');
        if (item.caution_if_change_value) checked.push('Caution if exceeds value');
        if (item.upgrade_is_below_value) checked.push('Upgrade is below value');
        if (item.caution_if_below_value) checked.push('Caution if below value');

        if (!checked.length) {
            return '<span class="text-xs text-gray-400">No condition selected</span>';
        }

        return checked.map((label) => `
            <span class="inline-flex items-center gap-2 text-emerald-700">
                <span class="inline-flex h-3.5 w-3.5 items-center justify-center rounded border border-emerald-600 bg-emerald-600 text-white">
                    <svg class="h-2.5 w-2.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.704 5.29a1 1 0 010 1.42l-7.25 7.25a1 1 0 01-1.415 0l-3-3a1 1 0 111.415-1.42l2.293 2.295 6.543-6.545a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                </span>
                ${esc(label)}
            </span>
        `).join('');
    }

    function renderCategories() {
        categoryListEl.innerHTML = categories.map((category) => {
            const active = Number(category.id) === Number(selectedCategoryId);
            return `
                <button type="button" data-category-id="${category.id}" class="w-full rounded-lg border px-3 py-2 text-left transition ${active ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-transparent bg-white text-gray-700 hover:border-gray-200 hover:bg-gray-50'}">
                    <div class="text-sm font-semibold">${esc(category.title)}</div>
                    <div class="text-xs ${active ? 'text-emerald-600' : 'text-gray-500'}">${Number(category.criteria_count || 0)} criteria</div>
                </button>
            `;
        }).join('');

        const current = activeCategory();
        activeCategoryEl.textContent = current ? current.title : 'Category';
    }

    function renderCriteria() {
        if (isLoadingCriteria) {
            criteriaListEl.innerHTML = `
                <div class="space-y-3 animate-pulse">
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="h-4 w-52 rounded bg-gray-200"></div>
                        <div class="mt-2 h-3 w-40 rounded bg-gray-100"></div>
                        <div class="mt-3 h-2 w-full rounded-full bg-gray-100"></div>
                        <div class="mt-3 h-3 w-64 rounded bg-gray-100"></div>
                    </div>
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="h-4 w-56 rounded bg-gray-200"></div>
                        <div class="mt-2 h-3 w-44 rounded bg-gray-100"></div>
                        <div class="mt-3 h-2 w-full rounded-full bg-gray-100"></div>
                        <div class="mt-3 h-3 w-60 rounded bg-gray-100"></div>
                    </div>
                </div>
            `;
            return;
        }

        if (!criteria.length) {
            criteriaListEl.innerHTML = '<div class="rounded-lg border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500">No criteria found for this category.</div>';
            return;
        }

        criteriaListEl.innerHTML = criteria.map((item, index) => `
            <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-900">${index + 1}. ${esc(item.name)}</p>
                        <p class="mt-1 text-xs text-gray-500">Unit: ${esc(item.unit || '-')} | Weight: ${Number(item.default_weight ?? 50)}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" data-edit-id="${item.id}" class="rounded-md border border-gray-200 px-2.5 py-1 text-xs font-semibold text-gray-600 hover:bg-gray-50">Edit</button>
                        <button type="button" data-delete-id="${item.id}" class="hidden rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
                    </div>
                </div>
                <div class="mt-3 h-2 w-full rounded-full bg-gray-200">
                    <div class="h-2 rounded-full bg-emerald-500" style="width:${Math.max(0, Math.min(100, Number(item.default_weight ?? 50)))}%"></div>
                </div>
                <div class="mt-3 flex flex-wrap items-center gap-5 text-xs text-gray-600">
                    ${renderCheckedConditions(item)}
                </div>
            </div>
        `).join('');
    }

    function renderTabs() {
        tabButtons.forEach((button) => {
            const isActive = button.dataset.kcTab === activeTab;
            button.classList.toggle('bg-emerald-600', isActive);
            button.classList.toggle('text-white', isActive);
            button.classList.toggle('shadow-sm', isActive);
            button.classList.toggle('text-gray-600', !isActive);
        });

        criteriaPanelEl.classList.toggle('hidden', activeTab !== 'property');
        aiPanelEl.classList.toggle('hidden', activeTab !== 'ai-table');
    }

    function renderAiSpecs() {
        if (isLoadingCriteria) {
            aiListEl.innerHTML = `
                <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden animate-pulse">
                    <div class="border-b border-gray-100 px-4 py-3">
                        <div class="h-4 w-40 rounded bg-gray-200"></div>
                    </div>
                    <div class="space-y-3 p-4">
                        <div class="h-10 rounded bg-gray-100"></div>
                        <div class="h-10 rounded bg-gray-100"></div>
                        <div class="h-10 rounded bg-gray-100"></div>
                    </div>
                </div>
            `;
            return;
        }

        const renderRows = (items, labelField = 'spec_label', mode = 'plain') => items.length
            ? items.map((item) => {
                const isKey = item.is_key_criteria === true;
                const removeBtn = `<button type="button" data-toggle-criteria-id="${item.id}" data-toggle-action="remove" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-red-200 text-red-500 hover:bg-red-50" title="Delete" aria-label="Delete key comparison"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg></button>`;
                const addBtn = `<button type="button" data-toggle-criteria-id="${item.id}" data-toggle-action="add" class="inline-flex h-7 w-7 items-center justify-center rounded-md border border-teal-200 text-teal-600 hover:bg-teal-50" title="Add to key comparison" aria-label="Add to key comparison"><svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg></button>`;
                const actionButton = mode === 'key' ? removeBtn : (mode === 'ai' && !isKey ? addBtn : '');

                return `
                    <div class="flex items-center justify-between gap-3 px-5 py-3 text-sm bg-white border-b border-gray-200 last:border-b-0">
                        <div class="flex flex-1 items-center gap-3">
                            ${actionButton}
                            <span class="text-gray-600 block">${esc(item[labelField] || item.spec_key || '-')}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs ${isKey ? 'rounded-full bg-emerald-50 px-2 py-1 font-semibold text-emerald-700' : 'rounded-full bg-gray-100 px-2 py-1 font-semibold text-gray-500'}">${isKey ? 'In key data' : 'AI only'}</span>
                            <span class="text-right font-semibold text-gray-900">${esc(item.unit || '-')}</span>
                        </div>
                    </div>
                `;
            }).join('')
            : '<div class="px-5 py-3 text-sm text-gray-500">No items found.</div>';

        const criteriaMidpoint = Math.ceil(criteria.length / 2);
        const criteriaLeftRows = criteria.slice(0, criteriaMidpoint);
        const criteriaRightRows = criteria.slice(criteriaMidpoint);

        const aiMidpoint = Math.ceil(aiSpecs.length / 2);
        const aiLeftRows = aiSpecs.slice(0, aiMidpoint);
        const aiRightRows = aiSpecs.slice(aiMidpoint);

        const keyComparisonSection = `
            <div class="mb-5 rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900">Key Comparison Data</h3>
                        <p class="text-xs text-gray-500">Critical specification definitions for the selected category.</p>
                    </div>
                </div>
                <div class="grid grid-cols-1 divide-y divide-gray-200 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="divide-y divide-gray-200">${criteria.length ? renderRows(criteriaLeftRows, 'name', 'key') : '<div class="px-5 py-3 text-sm text-gray-500">No key comparison data found for this category.</div>'}</div>
                    <div class="divide-y divide-gray-200">${criteria.length ? renderRows(criteriaRightRows, 'name', 'key') : '<div class="px-5 py-3 text-sm text-gray-500">No key comparison data found for this category.</div>'}</div>
                </div>
            </div>
        `;

        const aiCreateButton = `
            <button type="button" data-kc-action="open-ai-spec-modal" class="inline-flex items-center gap-2 rounded-lg border border-teal-200 bg-teal-50 px-3 py-1.5 text-xs font-semibold text-teal-700 transition hover:bg-teal-100">
                <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Create Specification
            </button>
        `;

        const aiSection = aiSpecs.length ? `
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">AI Pulled Specifications</h3>
                            <p class="text-xs text-gray-500">Category-level AI specification labels and units.</p>
                        </div>
                        ${aiCreateButton}
                    </div>
                </div>
                <div class="grid grid-cols-1 divide-y divide-gray-200 md:grid-cols-2 md:divide-x md:divide-y-0">
                    <div class="divide-y divide-gray-200">${renderRows(aiLeftRows, 'spec_label', 'ai')}</div>
                    <div class="divide-y divide-gray-200">${renderRows(aiRightRows, 'spec_label', 'ai')}</div>
                </div>
            </div>
        ` : `
            <div class="rounded-xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                <div class="border-b border-gray-200 bg-gray-50 px-5 py-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">AI Pulled Specifications</h3>
                            <p class="text-xs text-gray-500">Category-level AI specification labels and units.</p>
                        </div>
                        ${aiCreateButton}
                    </div>
                </div>
                <div class="p-6 text-center text-sm text-gray-500">No AI-pulled specifications found for this category.</div>
            </div>
        `;

        aiListEl.innerHTML = `
            ${keyComparisonSection}
            ${aiSection}
        `;
    }

    async function toggleAiCriteria(criteriaId, action) {
        const url = new URL(action === 'add'
            ? ADD_URL_BASE.replace('__ID__', String(criteriaId))
            : `${UPDATE_BASE_URL}/${criteriaId}`, window.location.origin);

        const res = await fetch(url.toString(), {
            method: action === 'add' ? 'POST' : 'DELETE',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ category_id: selectedCategoryId }),
        });

        const data = await res.json();
        if (res.ok && data.success) {
            await loadCriteria(selectedCategoryId);
        }
    }

    async function loadCriteria(categoryId) {
        const url = new URL(LIST_URL, window.location.origin);
        url.searchParams.set('category_id', String(categoryId));

        isLoadingCriteria = true;
        renderCriteria();

        try {
            const res = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
            const data = await res.json();

            if (!res.ok || !data.success) {
                criteria = [];
                aiSpecs = [];
                renderCriteria();
                renderAiSpecs();
                return;
            }

            criteria = data.items || [];
            aiSpecs = data.ai_items || [];
            const category = activeCategory();
            if (category) {
                category.criteria_count = criteria.length;
            }
            renderCriteria();
            renderAiSpecs();
        } catch (error) {
            criteria = [];
            aiSpecs = [];
            renderCriteria();
            renderAiSpecs();
        } finally {
            isLoadingCriteria = false;
            renderCriteria();
            renderAiSpecs();
        }
    }

    function resetForm() {
        editingId = null;
        modalTitle.textContent = 'Add Criterion';
        modalSave.textContent = 'Save';
        nameInput.value = '';
        unitInput.value = '';
        weightInput.value = '50';
        weightLabel.textContent = '50';
        upgradeInput.checked = false;
        cautionInput.checked = false;
        upgradeBelowInput.checked = false;
        cautionBelowInput.checked = false;
    }

    function openModal() {
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function resetAiSpecForm() {
        aiSpecNameInput.value = '';
        aiSpecUnitInput.value = '';
    }

    function openAiSpecModal() {
        aiSpecModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        aiSpecNameInput.focus();
    }

    function closeAiSpecModal() {
        aiSpecModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function updateCategoryCount(delta) {
        const category = activeCategory();
        if (!category) return;
        category.criteria_count = Math.max(0, Number(category.criteria_count || 0) + delta);
        renderCategories();
    }

    openFormBtn?.addEventListener('click', () => {
        resetForm();
        openModal();
    });

    modalOverlay?.addEventListener('click', closeModal);
    modalClose?.addEventListener('click', closeModal);
    modalCancel?.addEventListener('click', closeModal);

    aiSpecModalOverlay?.addEventListener('click', closeAiSpecModal);
    aiSpecModalClose?.addEventListener('click', closeAiSpecModal);
    aiSpecModalCancel?.addEventListener('click', closeAiSpecModal);

    weightInput?.addEventListener('input', () => {
        weightLabel.textContent = weightInput.value;
    });

    categoryListEl?.addEventListener('click', async (event) => {
        const button = event.target.closest('[data-category-id]');
        if (!button) return;

        const nextId = Number(button.dataset.categoryId || 0);
        if (!nextId || nextId === selectedCategoryId) return;

        selectedCategoryId = nextId;
        renderCategories();
        await loadCriteria(selectedCategoryId);
    });

    tabButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const nextTab = button.dataset.kcTab;
            if (!nextTab || nextTab === activeTab) return;

            activeTab = nextTab;
            renderTabs();
        });
    });

    criteriaListEl?.addEventListener('click', async (event) => {
        const editButton = event.target.closest('[data-edit-id]');
        if (editButton) {
            const targetId = Number(editButton.dataset.editId || 0);
            const item = criteria.find((row) => Number(row.id) === targetId);
            if (!item) return;

            editingId = targetId;
            modalTitle.textContent = 'Edit Criterion';
            modalSave.textContent = 'Update';
            nameInput.value = item.name || '';
            unitInput.value = item.unit || '';
            weightInput.value = String(item.default_weight ?? 50);
            weightLabel.textContent = String(item.default_weight ?? 50);
            upgradeInput.checked = item.upgrade_exceeds_value === true;
            cautionInput.checked = item.caution_if_change_value === true;
            upgradeBelowInput.checked = item.upgrade_is_below_value === true;
            cautionBelowInput.checked = item.caution_if_below_value === true;
            openModal();
            return;
        }

        const deleteButton = event.target.closest('[data-delete-id]');
        if (!deleteButton) return;

        const targetId = Number(deleteButton.dataset.deleteId || 0);
        if (!targetId) return;

        // Get the criterion name for the confirmation message
        const item = criteria.find((row) => Number(row.id) === targetId);
        if (!item) return;

        window.showConfirm(
            `Delete criterion "${item.name}"? This action cannot be undone.`,
            'Delete Criterion'
        ).then(async (result) => {
            if (!result.isConfirmed) return;

            const url = new URL(`${UPDATE_BASE_URL}/${targetId}`, window.location.origin);
            const res = await fetch(url.toString(), {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ category_id: selectedCategoryId }),
            });

            const data = await res.json();
            if (res.ok && data.success) {
                criteria = criteria.filter((row) => Number(row.id) !== targetId);
                updateCategoryCount(-1);
                renderCriteria();
            }
        });
    });

    aiListEl?.addEventListener('click', async (event) => {
        const createButton = event.target.closest('[data-kc-action="open-ai-spec-modal"]');
        if (createButton) {
            resetAiSpecForm();
            openAiSpecModal();
            return;
        }

        const button = event.target.closest('[data-toggle-criteria-id]');
        if (!button) return;

        const criteriaId = Number(button.dataset.toggleCriteriaId || 0);
        const action = button.dataset.toggleAction || 'add';
        if (!criteriaId) return;

        button.disabled = true;
        try {
            await toggleAiCriteria(criteriaId, action);
        } finally {
            button.disabled = false;
        }
    });

    aiSpecModalSave?.addEventListener('click', async () => {
        const payload = {
            category_id: selectedCategoryId,
            name: aiSpecNameInput.value.trim(),
            unit: aiSpecUnitInput.value.trim(),
        };

        if (!payload.name) {
            aiSpecNameInput.focus();
            return;
        }

        aiSpecModalSave.disabled = true;
        try {
            const res = await fetch(AI_SPEC_STORE_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });

            const data = await res.json();
            if (res.ok && data.success) {
                closeAiSpecModal();
                resetAiSpecForm();
                await loadCriteria(selectedCategoryId);
            }
        } finally {
            aiSpecModalSave.disabled = false;
        }
    });

    modalSave?.addEventListener('click', async () => {
        const payload = {
            category_id: selectedCategoryId,
            name: nameInput.value.trim(),
            unit: unitInput.value.trim(),
            default_weight: Number(weightInput.value || 50),
            upgrade_exceeds_value: upgradeInput.checked,
            caution_if_change_value: cautionInput.checked,
            upgrade_is_below_value: upgradeBelowInput.checked,
            caution_if_below_value: cautionBelowInput.checked,
        };

        if (!payload.name) {
            nameInput.focus();
            return;
        }

        if (!editingId) {
            const res = await fetch(STORE_URL, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(payload),
            });
            const data = await res.json();

            if (res.ok && data.success && data.item) {
                criteria.push(data.item);
                updateCategoryCount(1);
                renderCriteria();
                closeModal();
                resetForm();
            }
            return;
        }

        const updateUrl = new URL(`${UPDATE_BASE_URL}/${editingId}`, window.location.origin);
        const res = await fetch(updateUrl.toString(), {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': CSRF,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();

        if (res.ok && data.success && data.item) {
            criteria = criteria.map((row) => Number(row.id) === Number(editingId) ? data.item : row);
            renderCriteria();
            closeModal();
            resetForm();
        }
    });

    renderCategories();
    renderCriteria();
    renderAiSpecs();
    renderTabs();
});
</script>
@endpush
