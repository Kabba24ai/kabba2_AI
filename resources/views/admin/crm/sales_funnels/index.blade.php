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
                                    <button type="button" data-close-funnel-modal
                                        class="rounded-md p-2 text-slate-400 hover:bg-slate-100 hover:text-slate-600">
                                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                            stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
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
                                                <option value="new_lead_added" disabled>New Lead Added</option>
                                            </select>

                                            <!-- Note for Rental Start Date -->
                                            <div id="rentalStartDateNote" class="bg-blue-50 border border-blue-200 rounded-lg p-3 mt-2">
                                                <p class="text-sm text-blue-700">
                                                    <span class="font-semibold">Note:</span> This funnel will trigger on the scheduled delivery date at
                                                    <span id="triggerTimeDisplay">9:00 AM</span>
                                                    <span id="triggerTimingDisplay">(Before/After Event)</span>
                                                </p>
                                            </div>
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

                <div class="mt-6">
                    {{-- Filter by Category (Design only) --}}
                    <div class="bg-white rounded-lg border border-gray-200 p-4">
                        <div class="flex items-center gap-3 mb-3">
                            {{-- Filter icon --}}
                            <svg class="w-[18px] h-[18px] text-gray-500" viewBox="0 0 24 24" fill="none"
                                aria-hidden="true">
                                <path d="M3 4h18l-7 8v6l-4 2v-8L3 4z" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                            </svg>

                            <h3 class="font-medium text-gray-900">Filter by Category</h3>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            {{-- All Funnels (active example) --}}
                            <button type="button"
                                class="filter-category px-4 py-2 rounded-lg text-sm font-medium transition-all bg-blue-600 text-white shadow-sm border border-slate-900 shadow-sm"
                                data-value="">
                                All Funnels ({{ $totalFunnels }})
                            </button>

                            @foreach ($categories as $category)
                                <button type="button"
                                    class="filter-category px-4 py-2 rounded-lg text-sm font-medium transition-all text-gray-700 hover:opacity-90 text-white"
                                    style="background-color: {{ $category->color_code }};"
                                    data-value="{{ $category->id }}">
                                    {{ $category->category_name }} ({{ $category->funnels_count }})
                                </button>
                            @endforeach
                            {{-- Uncategorized (inactive example) --}}
                            <button type="button"
                                class="filter-category px-4 py-2 rounded-lg text-sm font-medium transition-all bg-gray-100 text-gray-700 hover:bg-gray-200"
                                data-value="unassigned">
                                Uncategorized ({{ $unassignedFunnels }})
                            </button>
                        </div>
                    </div>
                </div>

                <div class="mt-6 ">

                    <div id="funnels-loading" class="hidden"></div>

                    <div id="funnels-table-wrapper">
                        @include('admin.crm.sales_funnels.partials._table', ['funnels' => []])
                    </div>

                    <!-- Add Step Modal -->
                    <div id="addStepModal" class="fixed inset-0 z-9999 hidden" aria-hidden="true">
                        <!-- backdrop -->
                        <div class="absolute inset-0 bg-black/40" data-modal-backdrop></div>

                        <!-- modal -->
                        <div class="relative min-h-full flex items-center justify-center p-4">
                            <div
                                class="w-full max-w-3xl bg-white rounded-xl shadow-xl border border-gray-200 overflow-hidden">
                                <!-- header -->
                                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900" id="stepModalTitle">Add Funnel Step</h3>
                                    <button type="button" class="p-2 rounded-lg hover:bg-gray-50"
                                        data-close-add-step-modal aria-label="Close">
                                        <!-- X -->
                                        <svg class="w-5 h-5 text-gray-500" viewBox="0 0 24 24" fill="none"
                                            stroke="currentColor" stroke-width="2">
                                            <path d="M18 6 6 18M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>

                                <!-- body -->
                                <div class="px-6 py-5">
                                    <form id="stepForm" data-parsley-validate="true" class="space-y-6 py-4">
                                        <input type="hidden" id="funnel_unique_id" name="funnel_unique_id" value="">

                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <!-- Delay -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 required">
                                                    Delay from Funnel Start
                                                </label>

                                                <div class="mt-2 flex gap-3">
                                                    <select name="delay_unit" id="delay_unit" data-parsley-errors-container="#delay_unit-errors"
                                                        class="w-40 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                        <option value="Days">Days</option>
                                                        <option value="Hours">Hours</option>
                                                        <option value="Minutes">Minutes</option>
                                                    </select>

                                                    <input type="number" value="0" name="delay_value" data-parsley-errors-container="#delay_value-errors" id="delay_value"
                                                        class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                                                </div>
                                                <div id="delay_value-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                                                <div id="delay_unit-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                                                <p class="mt-2 text-xs text-gray-500">
                                                    {{-- <span class="font-semibold">Note:</span> --}}
                                                    Days: 1-30 | Hours: 1-24 | Minutes: 0, 15, 30, 45 | 0 = at funnel start
                                                </p>
                                            </div>

                                            <!-- Message type -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 required">
                                                    Message Type
                                                </label>

                                                <div class="mt-3 flex items-center gap-6">
                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="radio" name="step_type" value="SMS" checked required data-parsley-errors-container="#step_type-errors"
                                                            class="text-blue-600 focus:ring-blue-500">
                                                        <span class="inline-flex items-center gap-2">
                                                            <!-- sms icon -->
                                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="2">
                                                                <path
                                                                    d="M21 15a4 4 0 0 1-4 4H7l-4 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" />
                                                            </svg>
                                                            SMS
                                                        </span>
                                                    </label>

                                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                                        <input type="radio" name="step_type" value="Email" required data-parsley-errors-container="#step_type-errors"
                                                            class="text-blue-600 focus:ring-blue-500" disabled>
                                                        <span class="inline-flex items-center gap-2">
                                                            <!-- mail icon -->
                                                            <svg class="w-4 h-4 text-gray-500" viewBox="0 0 24 24"
                                                                fill="none" stroke="currentColor" stroke-width="2">
                                                                <path d="M4 4h16v16H4z" />
                                                                <path d="m22 6-10 7L2 6" />
                                                            </svg>
                                                            Email - coming soon
                                                        </span>
                                                    </label>
                                                </div>
                                                <div id="step_type-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                                            </div>

                                            <!-- Category -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">
                                                    Select Message Category
                                                </label>
                                                <select name="sms_category_id" id="smsCategorySelect" data-parsley-errors-container="#sms_category_id-errors"
                                                    class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="" selected>Select a category...</option>
                                                    @foreach ($smsCategories as $smsCategory)
                                                        <option value="{{ $smsCategory->id }}"
                                                            data-description="{{ $smsCategory->description }}">
                                                            {{ $smsCategory->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <div id="sms_category_id-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                                            </div>

                                            <!-- Message -->
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 required">
                                                    Select Message
                                                </label>
                                                <select name="sms_funnel_id" id="smsFunnelSelect" data-parsley-errors-container="#sms_funnel_id-errors"
                                                    class="mt-2 w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="" selected>Select a message...</option>
                                                </select>
                                                <div id="sms_funnel_id-errors" class="mt-1 text-sm text-red-600 dark:text-red-400"></div>
                                            </div>

                                        </div>

                                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 hidden" id="messagePreview">
                                            <h4 class="text-sm font-semibold text-gray-900 mb-2">Message Preview</h4>
                                            <div class="text-sm text-gray-700">
                                                <div class="whitespace-pre-wrap" id="messagePreviewContent">Please select a message to see the preview.</div>
                                            </div>
                                        </div>
                                        <!-- footer buttons -->
                                        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <button type="button"
                                                class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-white text-gray-700 font-medium hover:bg-gray-50"
                                                data-close-add-step-modal>
                                                Cancel
                                            </button>

                                            <button type="submit" id="addStepButton"
                                                class="w-full px-4 py-3 rounded-lg bg-blue-600 text-white font-semibold hover:bg-blue-700 disabled:opacity-50"
                                                disabled>
                                                Save Step
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
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


            function fetchCategories(page = 1, perPage = 30) {
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

            const categoryFilter = document.querySelectorAll('.filter-category');
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;
            const perPageParam = document.getElementById('per_page_sm')?.value || 10;

            const selectWrapper = document.getElementById('categorySelectWrapper');
            const inputWrapper = document.getElementById('categoryInputWrapper');
            const showBtn = document.getElementById('showCategoryInput');
            const backBtn = document.getElementById('backToSelect');

            // Track current active funnel
            let activeFunnelId = null;

            categoryFilter.forEach(btn => {
                btn.addEventListener('click', () => {
                    categoryFilter.forEach(b => {
                        b.classList.remove('border', 'border-slate-900', 'shadow-sm');
                    });

                    btn.classList.add('border', 'border-slate-900', 'shadow-sm');

                    // Fetch funnels for selected category
                    fetchFunnels(1, perPageParam);
                });
            });

            fetchFunnels(pageParam, perPageParam);

            function fetchFunnels(page = 1, perPage = 30) {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', perPage);
                const activeFilter = document.querySelector('.filter-category.border.border-slate-900.shadow-sm');
                if (activeFilter) {
                    params.append('category_id', activeFilter.getAttribute('data-value'));
                }

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

                            // Open and scroll to active funnel if exists
                            console.log('Active Funnel ID:', activeFunnelId);
                            if (activeFunnelId) {
                                const activeFunnelRow = document.getElementById('funnel-row-' + activeFunnelId);
                                if (activeFunnelRow) {
                                    // Open the panel
                                    const panel = activeFunnelRow.querySelector('[data-funnel-panel]');
                                    if (panel) {
                                        panel.setAttribute('aria-hidden', 'false');
                                        panel.classList.remove('hidden');
                                    }

                                    // Scroll into view with smooth behavior
                                    setTimeout(() => {
                                        activeFunnelRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                                    }, 100);
                                }
                            }
                        }
                    })
                    .finally(() => {
                        loader?.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                        const openers = document.querySelectorAll('[data-open-funnel-modal]');
                        openers.forEach(btn => btn.addEventListener('click', openModal));
                    });
            }

            // Pagination support
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchFunnels
            });

            // Make globally accessible if needed
            window.reloadFunnelsTable = fetchFunnels;

            // Expose active funnel ID tracker
            window.setActiveFunnel = (id) => {
                activeFunnelId = id;
            };

            window.getActiveFunnel = () => {
                return activeFunnelId;
            };


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
                            // Keep active funnel ID when reloading after save
                            if (editingFunnelId) {
                                activeFunnelId = editingFunnelId;
                            }else {
                                activeFunnelId = res.data.unique_id; // New funnel
                            }
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
                                        if (!document.querySelectorAll('.funnel-row').length) {
                                            // If no funnels left, reload table to show empty state
                                            reloadFunnelsTable();
                                        }
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
                btn.disabled = true;

                const uniqueId = btn.dataset.uniqueId;
                editingFunnelId = uniqueId;
                activeFunnelId = uniqueId; // Set active funnel when editing

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
                    }).finally(() => {
                        btn.disabled = false;
                    });
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.funnel-toggle-button');
                if (!btn) return;
                btn.disabled = true;
                const uniqueId = btn.dataset.uniqueId;
                activeFunnelId = uniqueId; // Track active funnel

                apiFetch(`{{ route('admin.crm.sales-funnels.status.toggle', ':unique_id') }}`
                        .replace(':unique_id', uniqueId), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                    .then(res => {
                        if (!res.success) return;
                        notyf.success(res.message);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        reloadFunnelsTable();
                    });
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.funnel-duplicate-button');
                if (!btn) return;
                btn.disabled = true;
                const uniqueId = btn.dataset.uniqueId;
                activeFunnelId = uniqueId; // Track active funnel

                apiFetch(`{{ route('admin.crm.sales-funnels.duplicate', ':unique_id') }}`
                        .replace(':unique_id', uniqueId), {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document
                                    .querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        })
                    .then(res => {
                        if (!res.success) return;
                        notyf.success(res.message);
                    })
                    .finally(() => {
                        btn.disabled = false;
                        reloadFunnelsTable();
                    });
            });

            const rentalStartDateNote = document.getElementById('rentalStartDateNote');
            const triggerEventSelect = document.getElementById('trigger_event');

            if (!rentalStartDateNote || !triggerEventSelect) return;

            const toggleNote = () => {
                rentalStartDateNote.classList.toggle(
                    'hidden',
                    triggerEventSelect.value !== 'rental_start_date'
                );
            };

            // Run on change
            triggerEventSelect.addEventListener('change', toggleNote);

            // Run once on page load (important)
            toggleNote();
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('addStepModal');
            const modalFunnelIdInput = document.getElementById('funnel_unique_id');
            if (!modal) return;

            const openModal = (funnelUniqueId) => {
                modalFunnelIdInput.value = funnelUniqueId;
                window.setActiveFunnel(funnelUniqueId); // Track the active funnel for step operations
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');
            };

            const closeModal = () => {
                modalFunnelIdInput.value = '';
                modal.classList.add('hidden');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('overflow-hidden');
                document.getElementById('stepModalTitle').textContent = 'Add Funnel Step';
                document.getElementById('stepForm').reset();
                document.getElementById('messagePreview').classList.add('hidden');
                document.getElementById('messagePreviewContent').textContent = 'Please select a message to see the preview.';
                editStepUniqueIdInput = document.getElementById('editStepUniqueId');
                if (editStepUniqueIdInput) {
                    editStepUniqueIdInput.remove();
                }
            };

            // Event delegation: open/close buttons anywhere on page (works with multiple partials)
            document.addEventListener('click', (e) => {
                // OPEN
                if (e.target.closest('[data-open-add-step]')) {
                    e.preventDefault();
                    const funnelId = e.target.closest('[data-open-add-step]').getAttribute(
                    'data-funnel-unique-id');
                    console.log('Opening Add Step Modal for Funnel ID:', funnelId);
                    openModal(funnelId);
                    return;
                }

                // CLOSE (supports both attributes in your markup)
                if (e.target.closest('[data-close-add-step-modal]')) {
                    e.preventDefault();
                    closeModal();
                    return;
                }

                // Backdrop click to close (clicking outside the modal content)
                if (e.target === modal) {
                    closeModal();
                }
            });

            // ESC to close
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                    closeModal();
                }
            });

            const delayValueInput = document.getElementById('delay_value');
            const delayUnitSelect = document.getElementById('delay_unit');
            delayUnitSelect.addEventListener('change', () => {
                if (delayUnitSelect.value === '') {
                    delayValueInput.value = 0;
                    delayValueInput.disabled = true;
                } else {
                    delayValueInput.disabled = false;

                    // Set input constraints based on delay unit
                    switch(delayUnitSelect.value) {
                        case 'Days':
                            delayValueInput.type = 'number';
                            delayValueInput.min = '1';
                            delayValueInput.max = '30';
                            delayValueInput.step = '1';
                            delayValueInput.placeholder = '1-30';
                            break;
                        case 'Hours':
                            delayValueInput.type = 'number';
                            delayValueInput.min = '1';
                            delayValueInput.max = '24';
                            delayValueInput.step = '1';
                            delayValueInput.placeholder = '1-24';
                            break;
                        case 'Minutes':
                            delayValueInput.type = 'number';
                            delayValueInput.min = '0';
                            delayValueInput.max = '45';
                            delayValueInput.step = '15';
                            delayValueInput.placeholder = '0, 15, 30, 45';
                            break;
                    }
                }
            });


            const form = document.getElementById('stepForm');

            if (!form) return; // safety guard

            form.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!$(this).parsley().isValid()) {
                    $(this).parsley().validate();
                    return;
                }

                const editStepUniqueIdInput = document.getElementById('editStepUniqueId');
                const url = editStepUniqueIdInput && editStepUniqueIdInput.value ?
                    `{{ route('admin.crm.sales-funnels.steps.update', ':step_unique_id') }}`
                    .replace(':step_unique_id', editStepUniqueIdInput.value) :
                    `{{ route('admin.crm.sales-funnels.steps.store') }}`;

                const formData = JSON.stringify({
                    funnel_unique_id: modalFunnelIdInput.value,
                    step_type: this.step_type.value,
                    delay_value: this.delay_value.value,
                    delay_unit: this.delay_unit.value,
                    sms_category_id: this.sms_category_id ? this.sms_category_id.value : null,
                    sms_funnel_id: this.sms_funnel_id ? this.sms_funnel_id.value : null,
                });

                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;

                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                apiFetch(url, {
                        method: editStepUniqueIdInput && editStepUniqueIdInput.value ? 'PUT' : 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res?.success) {
                            form.reset();
                            notyf.success(res.message);
                            window.setActiveFunnel(modalFunnelIdInput.value); // Keep active funnel
                            reloadFunnelsTable();
                            closeModal();
                        } else {
                            notyf.error(res?.message || 'Failed to add step');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            const smsCategorySelect = document.getElementById('smsCategorySelect');
            if (smsCategorySelect) {
                smsCategorySelect.addEventListener('change', function() {
                    messagePreview.classList.add('hidden')
                    smsFunnelSelect.innerHTML = '<option value="">Select message</option>';
                    const categoryId = this.value;
                    if (categoryId){
                        fetchSmsCategories(categoryId);
                    }
                });
            }


            const smsFunnelSelect = document.getElementById('smsFunnelSelect');
            const messagePreview = document.getElementById('messagePreview');
            const messagePreviewContent = document.getElementById('messagePreviewContent');
            const addStepButton = document.getElementById('addStepButton');
            if(smsFunnelSelect){
                smsFunnelSelect.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const content = selectedOption.dataset.content || 'No preview available';
                    messagePreviewContent.textContent = content;
                    messagePreview.classList.remove('hidden');
                    addStepButton.disabled = !this.value;
                });
            }

            function fetchSmsCategories(categoryId, preselectFunnelId = null) {

                smsFunnelSelect.innerHTML = '<option value="">Select message</option>';
                apiFetch(`{{ route('admin.crm.message-management.sms-funnel.fetch', ':categoryId') }}`
                        .replace(':categoryId', categoryId))
                    .then(res => {
                        if (res?.success) {
                            // Handle the fetched SMS categories as needed
                            res.funnels.forEach(funnel => {
                                const option = document.createElement('option');
                                option.value = funnel.id;
                                option.textContent = funnel.name;
                                option.dataset.content = funnel.description || '';
                                smsFunnelSelect.appendChild(option);
                            });

                            // Preselect funnel if provided
                            if (preselectFunnelId) {
                                smsFunnelSelect.value = preselectFunnelId;
                                smsFunnelSelect.dispatchEvent(new Event('change'));
                            }
                        } else {
                            notyf.error(res?.message || 'Failed to fetch SMS categories');
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        notyf.error('Something went wrong while fetching SMS categories');
                    });
            }

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.step-edit-button');
                if (!btn) return;
                btn.disabled = true;
                document.getElementById('stepForm').reset();
                // Parse step data from data attribute
                const dataStepJson = btn.getAttribute('data-step-json');
                if (!dataStepJson) {
                    btn.disabled = false;
                    return;
                }
                let stepData;
                try {
                    stepData = JSON.parse(dataStepJson);
                } catch (err) {
                    btn.disabled = false;
                    return;
                }

                // Populate modal fields
                modalFunnelIdInput.value = btn.getAttribute('data-funnel-unique-id');
                window.setActiveFunnel(modalFunnelIdInput.value); // Track the active funnel for step operations
                // Set step type radio
                const radios = document.getElementsByName('step_type');
                radios.forEach(radio => {
                    radio.checked = radio.value === stepData.step_type;
                });
                document.getElementById('delay_value').value = stepData.delay_value || 0;
                document.getElementById('delay_unit').value = stepData.delay_unit || 'Days';
                document.getElementById('delay_unit').dispatchEvent(new Event('change'));

                // Set SMS Category and fetch messages
                document.getElementById('smsCategorySelect').value = stepData.sms_category_id || '';
                fetchSmsCategories(stepData.sms_category_id, stepData.sms_message_id);

                // Set modal title and button
                document.getElementById('stepModalTitle').textContent = 'Edit Funnel Step';

                // Add hidden input to track edit mode
                let hidden = document.getElementById('editStepUniqueId');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'step_unique_id';
                    hidden.id = 'editStepUniqueId';
                    document.getElementById('stepForm').appendChild(hidden);
                }
                hidden.value = stepData.unique_id;

                // Show modal
                modal.classList.remove('hidden');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('overflow-hidden');

                btn.disabled = false;
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.step-delete-button');
                if (!btn) return;
                btn.disabled = true;

                const uniqueId = btn.getAttribute('data-step-unique-id');
                // Get the funnel unique ID from the step container
                const stepContainer = btn.closest('[data-funnel-card]');
                const funnelUniqueId = stepContainer ? stepContainer.id.replace('funnel-row-', '') : null;
                if (funnelUniqueId) {
                    window.setActiveFunnel(funnelUniqueId); // Track the active funnel for step operations
                }

                showConfirm(
                    'Do you want to delete this step?',
                    'This action cannot be undone.'
                ).then(result => {
                    if (!result.isConfirmed) {
                        btn.disabled = false;
                        return;
                    }

                    const url = `{{ route('admin.crm.sales-funnels.steps.delete', ':unique_id') }}`
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
                            reloadFunnelsTable();
                        } else {
                            notyf.error(res?.message || 'Failed to delete step');
                        }
                    })
                    .finally(() => {
                        btn.disabled = false;
                    });
                });
            });

        });
    </script>
@endpush
