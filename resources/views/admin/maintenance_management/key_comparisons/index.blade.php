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
                <button id="kc-open-form" type="button" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Criterion
                </button>
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
                </div>

                <div id="kc-criteria-list" class="space-y-3"></div>
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
                        <input id="kc-upgrade" type="checkbox" data-group="row-1" checked class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-exclusive">
                        Upgrade exceeds value
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-caution-below" type="checkbox" data-group="row-1" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-exclusive">
                        Caution if below value
                    </label>
                </div>

                <!-- Row 2 -->
                <div class="grid gap-2 md:grid-cols-2">
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-upgrade-below" type="checkbox" data-group="row-2" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-exclusive">
                        Upgrade is below value
                    </label>
                    <label class="flex items-center gap-2 text-xs text-gray-700">
                        <input id="kc-caution" type="checkbox" data-group="row-2" class="h-3.5 w-3.5 rounded border-gray-300 text-emerald-600 focus:ring-emerald-500 checkbox-exclusive">
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
@endsection

@push('js')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const categories = @json($categories ?? []);
    let selectedCategoryId = Number(@json($activeCategoryId ?? 0));
    let criteria = @json($initialCriteria ?? []);
    let editingId = null;

    const categoryListEl = document.getElementById('kc-category-list');
    const criteriaListEl = document.getElementById('kc-criteria-list');
    const activeCategoryEl = document.getElementById('kc-active-category');
    const openFormBtn = document.getElementById('kc-open-form');

    const modal = document.getElementById('kc-modal');
    const modalOverlay = document.getElementById('kc-modal-overlay');
    const modalClose = document.getElementById('kc-modal-close');
    const modalCancel = document.getElementById('kc-modal-cancel');
    const modalSave = document.getElementById('kc-modal-save');
    const modalTitle = document.getElementById('kc-modal-title');

    const nameInput = document.getElementById('kc-name');
    const unitInput = document.getElementById('kc-unit');
    const weightInput = document.getElementById('kc-weight');
    const weightLabel = document.getElementById('kc-weight-label');
    const upgradeInput = document.getElementById('kc-upgrade');
    const cautionInput = document.getElementById('kc-caution');
    const upgradeBelowInput = document.getElementById('kc-upgrade-below');
    const cautionBelowInput = document.getElementById('kc-caution-below');

    const LIST_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.index'));
    const STORE_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.store'));
    const UPDATE_BASE_URL = @json(route('admin.maintenance-management.key-comparisons.criteria.index'));
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
    let isLoadingCriteria = false;

    // Handle mutually exclusive checkboxes within each group
    document.addEventListener('change', (event) => {
        if (!event.target.classList.contains('checkbox-exclusive')) return;

        const group = event.target.dataset.group;
        if (!group || !event.target.checked) return;

        // Uncheck other checkboxes in the same group
        document.querySelectorAll(`[data-group="${group}"].checkbox-exclusive`).forEach((checkbox) => {
            if (checkbox !== event.target) {
                checkbox.checked = false;
            }
        });
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
                        <button type="button" data-delete-id="${item.id}" class="rounded-md border border-red-200 px-2.5 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Delete</button>
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
                renderCriteria();
                return;
            }

            criteria = data.items || [];
            renderCriteria();
        } catch (error) {
            criteria = [];
            renderCriteria();
        } finally {
            isLoadingCriteria = false;
            renderCriteria();
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
        upgradeInput.checked = true;
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
});
</script>
@endpush
