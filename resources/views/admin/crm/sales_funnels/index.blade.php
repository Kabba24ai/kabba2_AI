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
                    {{-- Funnel Modal --}}
                    <div id="funnelModal"
                        class="fixed inset-0 z-[99999] hidden flex items-center justify-center
                                bg-black/50 px-4 py-10">

                        <div class="modal-scrollable w-full mx-auto">
                            <div
                                class="bg-white rounded-lg shadow-xl w-full mx-auto max-w-lg
                                        border border-gray-200 overflow-hidden
                                        flex flex-col max-h-full">

                                <!-- ================= HEADER ================= -->
                                <div class="flex items-center justify-between px-6 py-4">
                                    <h3 id="funnelModalTitle" class="text-lg font-semibold text-slate-900">
                                        Create New Funnel
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

                                <!-- ================= BODY ================= -->
                                <div class="px-6 overflow-y-auto">
                                    <form id="funnelForm" data-parsley-validate= "true" class="space-y-6 py-4">

                                        <!-- Funnel Name -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1 required">
                                                Funnel Name
                                            </label>
                                            <input type="text" name="name" required
                                                class="w-full border border-gray-300 rounded-md
                                                        px-3 py-3 text-sm
                                                        focus:ring-2 focus:ring-blue-500"
                                                placeholder="e.g. Welcome Series">
                                        </div>

                                        <!-- Description -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Description
                                            </label>
                                            <textarea name="description" rows="3"
                                                class="w-full border border-gray-300 rounded-md
                                                            px-3 py-3 text-sm resize-none
                                                            focus:ring-2 focus:ring-blue-500"
                                                placeholder="Brief description of this funnel"></textarea>
                                        </div>

                                        <!-- Category -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                Category
                                            </label>

                                            <div class="flex gap-2">
                                                <select name="category_id" id="funnelCategorySelect"
                                                    class="flex-1 border border-gray-300 rounded-md
                                                            px-3 py-3 text-sm
                                                            focus:ring-2 focus:ring-blue-500">
                                                    <option value="">No Category</option>
                                                </select>

                                                <button type="button" id="showCategoryInput"
                                                    class="w-10 h-10 rounded-md border
                                                            border-gray-300 text-gray-600
                                                            hover:bg-gray-50">
                                                    +
                                                </button>
                                            </div>

                                            <div id="categoryInputWrapper" class="hidden mt-2">
                                                <input type="text" name="new_category"
                                                    class="w-full border border-gray-300 rounded-md
                                                            px-3 py-3 text-sm"
                                                    placeholder="Enter new category">
                                                <button type="button" id="backToSelect"
                                                    class="mt-1 text-xs text-blue-600 hover:underline">
                                                    ← Back to category list
                                                </button>
                                            </div>
                                        </div>

                                        <!-- Trigger Event -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1 required">
                                                Trigger Event
                                            </label>
                                            <select name="trigger_event" required
                                                class="w-full border border-gray-300 rounded-md
                                                     forwarding px-3 py-3 text-sm
                                                        focus:ring-2 focus:ring-blue-500">
                                                <option value="rental_start_date">Rental Start Date</option>
                                                <option value="new_lead_added">New Lead Added</option>
                                            </select>
                                        </div>

                                        <!-- Funnel Start Timing -->
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2 required">
                                                Funnel Start Timing
                                            </label>

                                            <!-- Before / After -->
                                            <div class="flex items-center gap-8 mb-3">
                                                <label class="inline-flex items-center gap-2 text-sm">
                                                    <input type="radio" name="timing" value="before">
                                                    Before Event
                                                </label>

                                                <label class="inline-flex items-center gap-2 text-sm">
                                                    <input type="radio" name="timing" value="after" checked>
                                                    After Event
                                                </label>
                                            </div>

                                            <!-- Timing Box -->
                                            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

                                                    <!-- Day -->
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            Day
                                                        </label>
                                                        <select name="date_value"
                                                            class="w-full border border-gray-300 rounded-md
                                                                    px-3 py-2 text-sm">
                                                            <option value="">Select</option>
                                                            @for ($i = 1; $i <= 31; $i++)
                                                                <option value="{{ $i }}">{{ $i }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>

                                                    <!-- Hour -->
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            Hour
                                                        </label>
                                                        <select name="hour_value"
                                                            class="w-full border border-gray-300 rounded-md
                                                                    px-3 py-2 text-sm">
                                                            <option value="">Select</option>
                                                            @for ($i = 1; $i <= 23; $i++)
                                                                <option value="{{ $i }}">{{ $i }}
                                                                </option>
                                                            @endfor
                                                        </select>
                                                    </div>

                                                    <!-- Minute -->
                                                    <div>
                                                        <label class="block text-xs font-medium text-gray-600 mb-1">
                                                            Minute
                                                        </label>
                                                        <select name="minute_value"
                                                            class="w-full border border-gray-300 rounded-md
                                                                    px-3 py-2 text-sm">
                                                            <option value="">Select</option>
                                                            <option value="15">15</option>
                                                            <option value="30">30</option>
                                                            <option value="45">45</option>
                                                        </select>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>

                                        <!-- Active -->
                                        <div>
                                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                <input type="checkbox" name="is_active" checked>
                                                Set as active
                                            </label>
                                        </div>

                                    </form>
                                </div>

                                <!-- ================= FOOTER ================= -->
                                <div class="flex justify-end gap-2 px-6 py-4 border-t">
                                    <button type="button" data-close-funnel-modal
                                        class="px-6 py-2 rounded-lg border
                                                border-gray-300 bg-white text-gray-700">
                                        Cancel
                                    </button>

                                    <button type="submit" id="funnelForm-btn" form="funnelForm"
                                        class="px-6 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                                        Create Funnel
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-6 ">
                    <div id="funnels-loading" class="hidden"></div>

                    <div id="funnels-table-wrapper">
                        @include('admin.crm.sales_funnels.partials._table', ['funnels' => []])
                    </div>

                </div>
            </div>

            {{-- TAB: Categories --}}
            <div data-tab-panel="categories" class="hidden">
                @include('admin.crm.sales_funnels.partials._tab_categories')

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
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                .search).get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;


            function initFunnelCategorySelect(selectedId = null) {
                const select = document.getElementById('funnelCategorySelect');
                if (!select) return;

                select.innerHTML = `<option value="">No Category</option>`;

                apiFetch("{{ route('admin.crm.sales-funnels.categories.all') }}")
                    .then(res => {
                        if (!res || !res.success || !Array.isArray(res.data)) return;

                        res.data.forEach(cat => {
                            const option = document.createElement('option');
                            option.value = cat.id;
                            option.textContent = cat.category_name;

                            if (selectedId && selectedId === cat.id) {
                                option.selected = true;
                            }

                            select.appendChild(option);
                        });
                    })
            }

            initFunnelCategorySelect();



            fetchCategories(pageParam, perPageParam); // initial fetch after loading saved filters

            function fetchCategories(page = 1, perPage = 10) {
                const params = new URLSearchParams();

                if (perPage) params.append('per_page', perPage);
                if (page) params.append('page', page);

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
                        initSingleDeleteButtons();
                        const openers = document.querySelectorAll('[data-open-category-modal]');
                        openers.forEach(btn => btn.addEventListener('click', openModal));
                    });

            }

            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchCategories
            });

            function initSingleDeleteButtons() {
                const singleDeleteButtons = document.querySelectorAll('.category-delete-button');

                singleDeleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const uniqueId = this.dataset.uniqueId;

                        showConfirm('Do you want to delete this note?', 'Are you sure?').then((
                            result) => {
                            if (result.isConfirmed) {
                                const url =
                                    '{{ route('admin.crm.sales-funnels.categories.delete', [':unique_id']) }}'
                                    .replace(':unique_id', uniqueId);

                                apiFetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector(
                                                'meta[name="csrf-token"]')
                                            .getAttribute('content'),
                                        'Accept': 'application/json'
                                    }
                                }).then(res => {
                                    if (res.success || (res.message && res.message
                                            .toLowerCase().includes('deleted'))) {
                                        notyf.success(res.message, 'Deleted!');
                                        const row = document.getElementById(
                                            'category-row-' +
                                            uniqueId);
                                        if (row) row.remove();

                                        reloadFunnelsTable();
                                    } else {
                                        notyf.error(res.message);
                                    }
                                })
                            }
                        });
                    });
                });
            }

            initSingleDeleteButtons();

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
                $(categoryForm).parsley().reset();
                document.getElementById('editCategoryId')?.remove(); // Remove hidden input if exists
                document.getElementById('categoryForm').reset();
                document.getElementById('categoryModalTitle').textContent = 'Add Category';
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

                const editCategoryId = document.getElementById('editCategoryId');

                if (editCategoryId && editCategoryId.value) {
                    // Editing existing category
                    url = '{{ route('admin.crm.sales-funnels.categories.update', [':unique_id']) }}'
                        .replace(':unique_id', editCategoryId.value);
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
                            // After a successful update
                            if (editCategoryId) {
                                editCategoryId.remove(); // Remove the hidden input
                            }
                        } else {
                            notyf.error(res && res.message ? res.message : '');
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            // Delegated event: Edit Category
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('button[title="Edit Category"]');
                if (!btn) return;

                // Get data from attributes
                const uniqueId = btn.getAttribute('data-unique_id');
                const categoryName = btn.getAttribute('data-category_name');
                const description = btn.getAttribute('data-description');
                const colorCode = btn.getAttribute('data-color_code');

                // Populate modal fields
                categoryNameInput.value = categoryName;
                descriptionInput.value = description;
                categoryColorInput.value = colorCode;
                syncColorSelection(colorCode);
                document.getElementById('categoryModalTitle').textContent = 'Edit Category';

                // Add hidden input to track edit mode
                let hidden = document.getElementById('editCategoryId');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'category_id';
                    hidden.id = 'editCategoryId';
                    document.getElementById('categoryForm').appendChild(hidden);
                }
                hidden.value = uniqueId;

                // Show modal
                document.getElementById('categoryModal').classList.remove('hidden');
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

            window.switchTab = setActive;

        });




        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('funnelModal');
            const openers = document.querySelectorAll('[data-open-funnel-modal]');
            const closers = document.querySelectorAll('[data-close-funnel-modal]');
            const funnelForm = document.getElementById('funnelForm');

            const loader = document.getElementById('funnels-loading');
            const wrapper = document.getElementById('funnels-table-wrapper');

            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;
            const perPageParam = document.getElementById('per_page_sm')?.value || 10;

            const selectWrapper = document.getElementById('categorySelectWrapper');
            const inputWrapper = document.getElementById('categoryInputWrapper');
            const showBtn = document.getElementById('showCategoryInput');
            const backBtn = document.getElementById('backToSelect');

            fetchFunnels(pageParam, perPageParam);

            function fetchFunnels(page = 1, perPage = 10) {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', perPage);

                loader?.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.crm.sales-funnels.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => {
                        if (res?.success) {
                            wrapper.innerHTML = res.html;

                            // rebind delete buttons
                            initSingleFunnelDeleteButtons();
                        }
                    })
                    .finally(() => {
                        loader?.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        const openers = document.querySelectorAll('[data-open-funnel-modal]');
                        openers.forEach(btn => btn.addEventListener('click', openModal));
                        console.log(openers);
                    });
            }

            // Pagination support
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchFunnels
            });

            // Make globally accessible if needed
            window.reloadFunnelsTable = fetchFunnels;


            function openModal() {
                resetFunnelForm();
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


            showBtn.addEventListener('click', () => {

                const closeFunnelBtn = document.querySelector('[data-close-funnel-modal]');
                if (closeFunnelBtn) {
                    closeFunnelBtn.click();
                }

                // Switch to Categories tab
                window.switchTab('categories');

                //  Open category modal
                const openCategoryBtn = document.querySelector('[data-open-category-modal]');
                if (openCategoryBtn) {
                    openCategoryBtn.click();
                }
            });

            backBtn.addEventListener('click', () => {
                inputWrapper.classList.add('hidden');
                selectWrapper.classList.remove('hidden');
            });

            funnelForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!$(funnelForm).parsley().isValid()) {
                    $(funnelForm).parsley().validate();
                    return;
                }

                const url = editingFunnelId ?
                    `{{ route('admin.crm.sales-funnels.update', ':id') }}`
                    .replace(':id', editingFunnelId) :
                    `{{ route('admin.crm.sales-funnels.store') }}`;

                const method = editingFunnelId ? 'PUT' : 'POST';

                const submitBtn = document.getElementById('funnelForm-btn');
                const originalText = submitBtn.textContent;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                apiFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            name: funnelForm.name.value,
                            description: funnelForm.description.value,
                            category_id: funnelForm.category_id.value,
                            trigger_event: funnelForm.trigger_event.value,
                            timing: funnelForm.timing.value,
                            date_value: funnelForm.date_value.value,
                            hour_value: funnelForm.hour_value.value,
                            minute_value: funnelForm.minute_value.value,
                            is_active: funnelForm.is_active.checked ? 1 : 0,
                        })
                    })
                    .then(res => {
                        if (res?.success) {
                            notyf.success(res.message);
                            closeFunnelModal();
                            reloadFunnelsTable();
                        } else {
                            notyf.error(res?.message || 'Failed');
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                        resetFunnelForm();
                    });
            });



            function initSingleFunnelDeleteButtons() {
                const deleteButtons = document.querySelectorAll('.funnel-delete-button');

                deleteButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const uniqueId = this.dataset.uniqueId;

                        showConfirm(
                            'Do you want to delete this funnel?',
                            'This action cannot be undone.'
                        ).then(result => {
                            if (!result.isConfirmed) return;

                            const url =
                                '{{ route('admin.crm.sales-funnels.delete', [':unique_id']) }}'
                                .replace(':unique_id', uniqueId);

                            apiFetch(url, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': document
                                            .querySelector('meta[name="csrf-token"]')
                                            .getAttribute('content'),
                                        'Accept': 'application/json'
                                    }
                                })
                                .then(res => {
                                    if (res?.success) {
                                        notyf.success(res.message);

                                        // Remove row from table
                                        const row = document.getElementById(
                                            'funnel-row-' + uniqueId);
                                        if (row) row.remove();
                                    } else {
                                        notyf.error(res?.message ||
                                            'Failed to delete funnel');
                                    }
                                });
                        });
                    });
                });
            }

            // --  edit funnal --

            let editingFunnelId = null;


            function openFunnelModal() {
                document.getElementById('funnelModal').classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeFunnelModal() {
                document.getElementById('funnelModal').classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
                resetFunnelForm();
            }

            function resetFunnelForm() {
                const form = document.getElementById('funnelForm');
                form.reset();

                editingFunnelId = null;

                document.getElementById('funnelModalTitle').textContent = 'Create New Funnel';
                document.getElementById('funnelForm-btn').textContent = 'Create Funnel';
            }


            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.funnel-edit-button');
                if (!btn) return;

                const uniqueId = btn.dataset.uniqueId;
                editingFunnelId = uniqueId;

                apiFetch(`{{ route('admin.crm.sales-funnels.show', ':id') }}`
                        .replace(':id', uniqueId))
                    .then(res => {
                        if (!res.success) return;

                        const f = res.data;
                        const form = document.getElementById('funnelForm');

                        form.name.value = f.funnel_name;
                        form.description.value = f.description ?? '';
                        form.category_id.value = f.sales_funnel_category_id ?? '';
                        form.trigger_event.value =
                            f.trigger_event === 'Rental Start Date' ?
                            'rental_start_date' :
                            'new_lead_added';

                        form.timing.value =
                            f.trigger_event_timing === 'Before Event' ?
                            'before' :
                            'after';

                        form.date_value.value = f.date_value ?? '';
                        form.hour_value.value = f.hour_value ?? '';
                        form.minute_value.value = f.minute_value ?? '';
                        form.is_active.checked = f.status === 'Active';

                        document.getElementById('funnelModalTitle').textContent = 'Edit Funnel';
                        document.getElementById('funnelForm-btn').textContent = 'Update Funnel';

                        openFunnelModal();
                    });
            });


        });
    </script>
@endpush
