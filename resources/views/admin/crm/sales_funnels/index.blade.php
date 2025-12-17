@extends('admin.layouts.app')

@section('title', 'Sales Funnels')

@push('css')
@endpush

@section('content')
    <div class="w-full">
        {{-- Header --}}
        <div class="rounded-t-2xl bg-white px-6 pt-6">
            <h1 class="text-2xl font-semibold text-slate-900">Sales Funnel Automation</h1>
            <p class="mt-1 text-sm text-slate-500">Build and organize your automated sales funnels</p>

            {{-- Tabs --}}
            <div class="mt-6 border-b border-slate-200">
                <nav class="-mb-px flex gap-8" aria-label="Tabs">
                    <button type="button" data-tab-btn="funnels"
                        class="tab-btn group inline-flex items-center gap-2 border-b-2 border-blue-600 px-1 pb-3 text-sm font-medium text-blue-600">
                        <svg class="h-5 w-5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h10M4 18h16" />
                        </svg>
                        Funnel Builder
                    </button>

                    <button type="button" data-tab-btn="categories"
                        class="tab-btn group inline-flex items-center gap-2 border-b-2 border-transparent px-1 pb-3 text-sm font-medium text-slate-600 hover:text-slate-900 hover:border-slate-300">
                        <svg class="h-5 w-5 text-slate-500 group-hover:text-slate-700" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 7a2 2 0 0 1 2-2h5l2 2h10a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" />
                        </svg>
                        Categories
                    </button>
                </nav>
            </div>
        </div>

        {{-- Body --}}
        <div class="rounded-b-2xl bg-white px-6 py-6">

            {{-- TAB: Funnels --}}
            <div data-tab-panel="funnels">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Sales Funnels</h2>
                    </div>

                    <button type="button" data-open-funnel-modal
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Create Funnel
                    </button>

                    {{-- Funnel Modal --}}
                    {{-- Modal --}}
                    <div id="funnelModal" class="fixed inset-0 z-9999 hidden">
                        {{-- Overlay --}}
                        <div data-close-funnel-modal class="absolute inset-0 bg-black/40"></div>

                        {{-- Dialog --}}
                        <div class="relative mx-auto flex min-h-screen max-w-lg items-center justify-center px-4">
                            <div class="w-full max-w-xl overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog"
                                aria-modal="true" aria-labelledby="funnelModalTitle">
                                {{-- Header --}}
                                <div class="flex items-center justify-between px-6 py-4">
                                    <h3 id="funnelModalTitle" class="text-lg font-semibold text-slate-900">Create New Funnel
                                    </h3>
                                    <button type="button" data-close-funnel-modal
                                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="h-px bg-slate-200"></div>

                                {{-- Body --}}
                                <form id="funnelForm" class="px-6 py-5">
                                    {{-- Funnel Name --}}
                                    <div>
                                        <label class="text-sm font-medium text-slate-700">Funnel Name <span
                                                class="text-red-500">*</span></label>
                                        <input type="text" name="name" required
                                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="e.g., Welcome Series" />
                                    </div>

                                    {{-- Description --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Description</label>
                                        <textarea name="description" rows="4"
                                            class="mt-2 w-full resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="Brief description of this funnel's purpose"></textarea>
                                    </div>

                                    {{-- Category + plus --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Category</label>

                                        <div class="mt-2 flex items-center gap-3">
                                            <select name="category_id"
                                                class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                                <option value="">No Category</option>
                                                {{-- add your categories here --}}
                                                {{-- <option value="1">Category 1</option> --}}
                                            </select>

                                            <button type="button"
                                                class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-700 hover:bg-slate-50"
                                                aria-label="Add Category" title="Add Category" data-open-category-modal>
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor" stroke-width="2">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 4v16m8-8H4" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Trigger Event --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Trigger Event <span
                                                class="text-red-500">*</span></label>

                                        <select name="trigger_event" required
                                            class="mt-2 w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                            <option value="rental_start_date" selected>Rental Start Date</option>
                                            <option value="new_lead_added">New Lead Added</option>
                                        </select>

                                        <p class="mt-2 text-xs text-slate-500">Triggers based on the rental start date</p>
                                    </div>

                                    {{-- Funnel Start Timing --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Funnel Start Timing <span
                                                class="text-red-500">*</span></label>

                                        <div class="mt-2 flex items-center gap-10">
                                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                <input type="radio" name="timing" value="before" class="h-4 w-4" />
                                                Before Event
                                            </label>

                                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                                <input type="radio" name="timing" value="after" class="h-4 w-4"
                                                    checked />
                                                After Event
                                            </label>
                                        </div>

                                        <div class="mt-3 grid grid-cols-2 gap-3">
                                            <select name="offset_unit"
                                                class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200">
                                                <option value="days" selected>Days</option>
                                                <option value="hours">Hours</option>
                                                <option value="minutes">Minutes</option>
                                            </select>

                                            <input type="number" name="offset_value" value="0" min="0"
                                                class="rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200" />
                                        </div>

                                        <p class="mt-2 text-xs text-slate-500">Funnel starts at the trigger event</p>
                                    </div>

                                    {{-- Active --}}
                                    <div class="mt-4">
                                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="is_active"
                                                class="h-4 w-4 rounded border-slate-300" checked />
                                            Set as active
                                        </label>
                                    </div>

                                    {{-- Footer --}}
                                    <div class="mt-6 flex items-center gap-4">
                                        <button type="button" data-close-funnel-modal
                                            class="w-1/2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            Cancel
                                        </button>

                                        <button type="submit"
                                            class="w-1/2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                            Create
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-6 rounded-2xl border-2 border-dashed border-slate-300 bg-white p-12">
                    <div class="mx-auto flex max-w-md flex-col items-center text-center">
                        <div class="flex h-14 w-14 items-center justify-center rounded-full bg-slate-100">
                            <svg class="h-7 w-7 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                                stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                        </div>

                        <h3 class="mt-4 text-lg font-semibold text-slate-900">No funnels yet</h3>
                        <p class="mt-1 text-sm text-slate-500">Create your first funnel to get started</p>

                        <button type="button"
                            class="mt-6 inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                            Create Your First Funnel
                        </button>
                    </div>
                </div>
            </div>

            {{-- TAB: Categories --}}
            <div data-tab-panel="categories" class="hidden">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-900">Category Management</h2>
                        <p class="mt-1 text-sm text-slate-500">Organize your sales funnels with categories</p>
                    </div>

                    <button type="button" data-open-category-modal
                        class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        Add Category
                    </button>

                    {{-- Modal --}}
                    <div id="categoryModal" class="fixed inset-0 z-9999 hidden">
                        {{-- Overlay --}}
                        <div data-close-category-modal class="absolute inset-0 bg-black/40"></div>

                        {{-- Dialog --}}
                        <div class="relative mx-auto flex min-h-screen max-w-lg items-center justify-center px-4">
                            <div class="w-full overflow-hidden rounded-2xl bg-white shadow-xl" role="dialog"
                                aria-modal="true" aria-labelledby="categoryModalTitle">
                                {{-- Header --}}
                                <div class="flex items-center justify-between px-6 py-4">
                                    <h3 id="categoryModalTitle" class="text-lg font-semibold text-slate-900">
                                        Add Category
                                    </h3>
                                    <button type="button" data-close-category-modal
                                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <div class="h-px bg-slate-200"></div>

                                {{-- Body --}}
                                {{ html()->form()->attributes([
                                        'data-parsley-validate' => true,
                                        'class' => 'px-6 py-5',
                                        'id' => 'categoryForm',
                                    ])->open() }}
                                    {{-- Category Name --}}
                                    <div>
                                        <label class="text-sm font-medium text-slate-700 required" for="categoryName">
                                            Category Name
                                        </label>
                                        <input type="text" name="name" id="categoryName"
                                            class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="Enter category name" required />
                                    </div>

                                    {{-- Description --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Description</label>
                                        <textarea name="description" rows="4" id="description"
                                            class="mt-2 w-full resize-none rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-200"
                                            placeholder="Enter description"></textarea>
                                    </div>

                                    {{-- Color Picker --}}
                                    <div class="mt-4">
                                        <label class="text-sm font-medium text-slate-700">Color <span
                                                class="text-red-500">*</span></label>

                                        <input type="hidden" name="color" id="categoryColor" value="#34d399"
                                            required />

                                        <div class="mt-3 grid grid-cols-4 gap-3">
                                            @php
                                                $colors = [
                                                    '#3b82f6', // blue
                                                    '#34d399', // green
                                                    '#f59e0b', // orange
                                                    '#ef4444', // red
                                                    '#8b5cf6', // purple
                                                    '#ec4899', // pink
                                                    '#2dd4bf', // teal
                                                    '#6366f1', // indigo
                                                ];
                                            @endphp

                                            @foreach ($colors as $c)
                                                <button type="button" data-color-btn data-color="{{ $c }}"
                                                    class="relative h-12 w-full rounded-lg"
                                                    style="background: {{ $c }};"
                                                    aria-label="Pick color {{ $c }}">
                                                    <span
                                                        class="pointer-events-none absolute inset-0 rounded-lg ring-2 ring-transparent"></span>
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>

                                    {{-- Footer Buttons --}}
                                    <div class="mt-6 flex items-center gap-4">
                                        <button type="button" data-close-category-modal
                                            class="w-1/2 rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50">
                                            Cancel
                                        </button>

                                        <button type="submit"
                                            class="w-1/2 rounded-lg bg-blue-600 px-4 py-2.5 text-sm font-medium text-white hover:bg-blue-700">
                                            Add Category
                                        </button>
                                    </div>
                                {{ html()->form()->close() }}
                            </div>
                        </div>
                    </div>

                </div>

                <div id="categories-table-wrapper" aria-live="polite" class="mt-6">
                    @include('admin.crm.sales_funnels.categories._table', ['categories' => []])
                </div>
            </div>

        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let loader = document.querySelector('#categories-loading');
            let wrapper = document.querySelector('#categories-table-wrapper');
            let timeout = null;
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            fetchCategories(pageParam, perPageParam); // initial fetch after loading saved filters

            function fetchCategories(page = 1, perPage = 10) {
                const params = new URLSearchParams();
                params.set('page', 1); // Always reset to first page on filter
                if (perPage) params.append('per_page', perPage);

                // Show loader
                loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.crm.sales-funnels.categories.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                    })
                    .finally(() => {
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });

            }

            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchCategories
            });

            const modal = document.getElementById('categoryModal');
            const openers = document.querySelectorAll('[data-open-category-modal]');
            const closers = document.querySelectorAll('[data-close-category-modal]');
            const colorInput = document.getElementById('categoryColor');
            const colorButtons = document.querySelectorAll('[data-color-btn]');

            function openModal() {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
                // apply default selection highlight
                syncColorSelection(colorInput.value);
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            function syncColorSelection(hex) {
                colorButtons.forEach(btn => {
                    const ring = btn.querySelector('span');
                    const isActive = btn.dataset.color.toLowerCase() === hex.toLowerCase();
                    ring.classList.toggle('ring-slate-900', isActive);
                    ring.classList.toggle('ring-2', true);
                    ring.classList.toggle('ring-transparent', !isActive);
                    ring.classList.toggle('ring-offset-2', isActive);
                    ring.classList.toggle('ring-offset-white', isActive);
                });
            }

            openers.forEach(btn => btn.addEventListener('click', openModal));
            closers.forEach(btn => btn.addEventListener('click', closeModal));

            // ESC to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });

            // color select
            colorButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const hex = btn.dataset.color;
                    colorInput.value = hex;
                    syncColorSelection(hex);
                });
            });

            const categoryForm = document.getElementById('categoryForm');
            const categoryNameInput = document.getElementById('categoryName');
            const categoryColorInput = document.getElementById('categoryColor');
            const descriptionInput = document.getElementById('description');

            // Handle categoryForm form submit (AJAX logic to be added as needed)
            categoryForm.addEventListener('submit', function(e) {

                e.preventDefault();

                if (!$(categoryForm).parsley().isValid()) {
                    $(categoryForm).parsley().validate();
                    return;
                }

                let url;
                let method = 'POST';
                // Save category via AJAX using apiFetch
                const categoryNameValue = categoryNameInput.value.trim();
                const categoryColorValue = categoryColorInput.value.trim();
                const descriptionValue = descriptionInput.value.trim();

                const editField = document.getElementById('editFieldId');

                if (editField && editField.value) {
                    // Editing existing category
                    // url =
                    //     '{{ route('admin.crm.sales-funnels.categories.store', [':category_id']) }}'
                    //     .replace(':category_id', editField.value);
                    method = 'PUT';
                } else {
                    // Adding new category
                    url = '{{ route('admin.crm.sales-funnels.categories.store') }}';

                }

                const submitBtn = categoryForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                apiFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            category_name: categoryNameValue,
                            color_code: categoryColorValue,
                            description: descriptionValue
                        })
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            closeModal();
                            categoryForm.reset();
                            fetchCategories();
                        } else {
                            notyf.error(res && res.message ? res.message : '');
                        }
                    })
                    .finally(() => {
                        // After a successful update
                        if (editField) {
                            editField.remove(); // Remove the hidden input
                        }
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            const editCategoryButtons = document.querySelectorAll('.edit-category-button');
            editCategoryButtons.forEach(button => {
                button.addEventListener('click', () => {
                    console.log('Edit button clicked');
                    const uniqueId = button.getAttribute('data-unique_id');
                    const categoryName = button.getAttribute('data-category_name');
                    const description = button.getAttribute('data-description');
                    const colorCode = button.getAttribute('data-color_code');
                    // Populate the form fields
                    categoryNameInput.value = categoryName;
                    descriptionInput.value = description;
                    categoryColorInput.value = colorCode;
                    syncColorSelection(colorCode);
                    // Add a hidden input to indicate edit mode
                    let editField = document.getElementById('editFieldId');
                    if (!editField) {
                        editField = document.createElement('input');
                        editField.type = 'hidden';
                        editField.id = 'editFieldId';
                        editField.name = 'edit_unique_id';
                        categoryForm.appendChild(editField);
                    }
                    editField.value = uniqueId;
                    // Update modal title
                    document.getElementById('categoryModalTitle').textContent = 'Edit Category';
                    // Update submit button text
                    const submitBtn = categoryForm.querySelector('button[type="submit"]');
                    submitBtn.textContent = 'Update Category';
                    // Open the modal
                    openModal();
                });
            });
        });

        document.addEventListener('DOMContentLoaded', () => {
            const buttons = Array.from(document.querySelectorAll('[data-tab-btn]'));
            const panels = Array.from(document.querySelectorAll('[data-tab-panel]'));

            function setActive(tabName) {
                panels.forEach(p => {
                    p.classList.toggle('hidden', p.getAttribute('data-tab-panel') !== tabName);
                });

                buttons.forEach(btn => {
                    const isActive = btn.getAttribute('data-tab-btn') === tabName;

                    btn.classList.toggle('text-blue-600', isActive);
                    btn.classList.toggle('border-blue-600', isActive);

                    btn.classList.toggle('text-slate-600', !isActive);
                    btn.classList.toggle('border-transparent', !isActive);

                    const svg = btn.querySelector('svg');
                    if (svg) {
                        svg.classList.toggle('text-blue-600', isActive);
                        svg.classList.toggle('text-slate-500', !isActive);
                    }
                });
            }

            buttons.forEach(btn => {
                btn.addEventListener('click', () => setActive(btn.getAttribute('data-tab-btn')));
            });

            // Default tab
            setActive('funnels');
        });


        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('funnelModal');
            const openers = document.querySelectorAll('[data-open-funnel-modal]');
            const closers = document.querySelectorAll('[data-close-funnel-modal]');
            const form = document.getElementById('funnelForm');

            function openModal() {
                modal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeModal() {
                modal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            openers.forEach(btn => btn.addEventListener('click', openModal));
            closers.forEach(btn => btn.addEventListener('click', closeModal));

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal();
            });

            // Demo submit (replace with ajax)
            form.addEventListener('submit', (e) => {
                e.preventDefault();

                const payload = {
                    name: form.name.value,
                    description: form.description.value,
                    category_id: form.category_id.value,
                    trigger_event: form.trigger_event.value,
                    timing: form.timing.value,
                    offset_unit: form.offset_unit.value,
                    offset_value: form.offset_value.value,
                    is_active: form.is_active.checked ? 1 : 0,
                };

                console.log('Funnel payload:', payload);
                closeModal();
            });
        });
    </script>
@endpush
