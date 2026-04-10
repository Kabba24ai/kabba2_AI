@extends('admin.layouts.app')

@section('title', 'Schedule Assignment')

@push('css')
    <style>
        .tw-tooltip::before {
            content: "";
            position: absolute;
            top: -6px;
            left: 16px;
            width: 10px;
            height: 10px;
            background: #fff;
            border-left: 1px solid rgb(229 231 235);
            /* gray-200 */
            border-top: 1px solid rgb(229 231 235);
            transform: rotate(45deg);
        }
    </style>
@endpush

@section('content')

    @include('flash::message')

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center gap-2">
            <svg class=" w-7 h-7 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Schedule Assignment </h3>
        </div>
        <a href="{{ route('admin.order-management.schedule-assignment.index') }}" id="reloadBtn"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
            <svg id="reloadIcon" class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0
                                             3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1
                                             13.803-3.7l3.181 3.182m0-4.991v4.99" />
            </svg>
            Reload
        </a>
    </div>

    <div class="bg-white rounded-lg p-4 border border-gray-200 shadow-sm mb-6">

        <div class="flex flex-col gap-3 md:flex-row md:items-center md:flex-wrap">
            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear
                </button>
            </div>

            {{-- Search Input --}}
            <div class="relative w-full md:w-56">
                <input type="text" name="search" placeholder="Search equipment, ID, or customer"
                    value="{{ request('search') }}"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-4 pr-10 text-sm text-gray-900 shadow-sm" />
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
            </div>

            {{-- Category --}}
            <div class="w-full md:w-46">
                <select name="category"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm">
                    <option value="">All Product Categories</option>
                    @foreach ($categories as $id => $title)
                        <option value="{{ $id }}" @selected(request('category') == $id)>
                            {{ $title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Store --}}
            <div class="w-full md:w-30">
                <select name="store"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(request('store') == $store->id)>
                            {{ $store->store_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="w-full md:w-46">
                <select name="assignment_filter" id="assignment_filter"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm">
                    <option value="all" @selected((request('assignment_filter') ?? 'all') === 'all')>Show All Equipment</option>
                    <option value="assigned" @selected(request('assignment_filter') === 'assigned')>Only Show Assigned</option>
                    <option value="assigned_3_days" @selected(request('assignment_filter') === 'assigned_3_days')>Only Show 3 Days Assigned</option>
                </select>
            </div>

            @php
                $pill =
                    'inline-flex items-center h-10 gap-3 rounded-md border border-gray-300 bg-white px-3 text-sm text-gray-900 shadow-sm whitespace-nowrap';
                $chk = 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500';
                $lbl = 'inline-flex items-center gap-2 cursor-pointer select-none';
            @endphp

            {{-- Hide Unassigned --}}
            <div class="{{ $pill }}">
                <label class="{{ $lbl }}">
                    <input type="checkbox" name="hide_unassigned" value="1" id="hide_unassigned"
                        class="{{ $chk }}" {{ request('hide_unassigned') ? 'checked' : '' }}>
                    Hide Unassigned
                </label>
            </div>

            {{-- Equipment Status --}}
            <div class="{{ $pill }}">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <span class="font-medium">Equipment Status</span>

                <label class="{{ $lbl }}">
                    <input type="checkbox" value="Available" name="equipment_status[]" class="{{ $chk }}"
                        {{ request('equipment_status') === null || in_array('Available', (array) request('equipment_status')) ? 'checked' : '' }}>
                    Available
                </label>

                <label class="{{ $lbl }}">
                    <input type="checkbox" value="Rented" name="equipment_status[]" class="{{ $chk }}"
                        {{ request('equipment_status') === null || in_array('Rented', (array) request('equipment_status')) ? 'checked' : '' }}>
                    Rented
                </label>
            </div>

            {{-- Issues & Maintenance --}}
            <div class="{{ $pill }}">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                    stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 text-orange-500">
                    <path
                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                    </path>
                </svg>
                <span class="font-medium">Issues & Maint.</span>

                <label class="{{ $lbl }}">
                    <input type="checkbox" name="equipment_status[]" value="Maintenance" class="{{ $chk }}"
                        {{ request('equipment_status') === null || in_array('Maintenance', (array) request('equipment_status')) ? 'checked' : '' }}>
                    Maint. Hold
                </label>

                <label class="{{ $lbl }}">
                    <input type="checkbox" name="equipment_status[]" value="Damaged" class="{{ $chk }}"
                        {{ request('equipment_status') === null || in_array('Damaged', (array) request('equipment_status')) ? 'checked' : '' }}>
                    Damaged
                </label>
            </div>

            {{-- Past 7 days --}}
            <div class="{{ $pill }}">
                <label class="{{ $lbl }}">
                    <input type="checkbox" name="past_seven_days" value="1" id="past_seven_days"
                        class="{{ $chk }}" {{ request('past_seven_days') ? 'checked' : '' }}>
                    Past 7 days
                </label>
            </div>

            {{-- Overdue --}}
            <div class="{{ $pill }}">
                <label class="{{ $lbl }}">
                    <input type="checkbox" name="overdue" value="1" id="overdue"
                        class="{{ $chk }}" {{ request('overdue') ? 'checked' : '' }}>
                    Overdue
                </label>
            </div>

        </div>

    </div>

    {{-- Equipment name group bar --}}
    <div id="equipment-group-bar" class="hidden bg-white rounded-lg border border-gray-200 shadow-sm px-3 py-2 mb-2">
        <div class="flex gap-2 overflow-x-auto flex-nowrap pb-0.5">
            {{-- pills rendered by JS --}}
        </div>
    </div>

    <div class="flex flex-col gap-5 h-[calc(90vh-180px)] min-h-0"> {{-- adjust 180px as needed --}}
        {{-- Equipment table --}}
        <div id="equipment-table-wrapper"
            class="bg-white shadow-sm rounded-lg overflow-x-auto overflow-y-auto flex-1 min-h-0">
            @include('admin.order_management.schedule_assignment.partials._table', [
                'equipment' => [],
            ])
        </div>

        {{-- Schedule table (only if there are unassigned orders) --}}

        <div class="bg-white shadow-sm rounded-lg flex-1 flex flex-col min-h-0"
            id="unassignedOrder">
            <h2 class="text-lg font-semibold p-5">
                Unassigned Orders <span id="unassignedOrderCount"></span>
            </h2>

            <div id="schedule-table-wrapper" class="overflow-x-auto overflow-y-auto flex-1 min-h-0">
                @include('admin.order_management.schedule_assignment.partials._schedule_table', [
                    'orderProducts' => [],
                ])
            </div>
        </div>
    </div>

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="relative px-6 pt-6 pb-4 border-b">
                <h2 class="text-xl font-semibold text-gray-900 text-center">Assign Equipment</h2>
                <button type="button"
                    class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none absolute right-6 top-6">&times;</button>
            </div>

            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'equipmentAssignForm',
                ])->open() }}

            <div class="px-4 pt-3 space-y-2 overflow-y-auto">
                <!-- Order Information Section -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Order ID:</span>
                        <a id="assign-order-id" href="#" target="_blank" class="text-sm text-blue-900 font-semibold">-</a>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Customer:</span>
                        <span id="assign-customer-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Product Ordered:</span>
                        <span id="assign-product-name" class="text-sm text-blue-900 font-semibold">-</span>
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required" for="user_unique_id">User</label>
                    <select name="user_unique_id" id="user_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                        required>
                        <option value="">Select Employee</option>
                        @foreach ($employees as $employeeId => $employeeName)
                            <option value="{{ $employeeId }}">{{ $employeeName }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required">Category</label>
                    <select id="category_select"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm bg-white text-gray-700">
                        <option value="">Select Category</option>
                    </select>
                </div>


                <div class="space-y-1">
                    <label class="text-sm font-medium text-gray-700 required" for="equipment_unique_id">Equipment</label>
                    <select name="equipment_unique_id" id="equipment_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                        <option value="" data-current-status="">Select Equipment</option>
                    </select>
                    <div class="flex items-center justify-between gap-3 mt-2">
                        <span id="equipment-status-display" class="text-sm font-semibold text-yellow-400"></span>
                        <a href="#" target="_blank" class="text-blue-600 hover:underline text-sm font-semibold"
                            id="equipment-page-link"></a>
                    </div>
                </div>
                <input type="hidden" id="order-product-unique-id" name="order_product_unique_id" value="">
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-equipment-assign-modal px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="equipment-assign-submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.__scheduleTooltipInitialized) return;
            window.__scheduleTooltipInitialized = true;

            const tooltip = document.createElement('div');
            tooltip.className =
                'fixed hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 shadow-lg max-w-xs z-[9999]';
            document.body.appendChild(tooltip);

            let activeTrigger = null;
            let timeouts = {
                open: null,
                close: null
            };

            const DELAYS = {
                open: 120,
                close: 80
            };
            const GAP = 8;

            function hide() {
                clearTimeout(timeouts.open);
                clearTimeout(timeouts.close);
                activeTrigger = null;
                tooltip.classList.add('hidden');
            }

            function position(trigger) {
                if (!trigger) return;

                tooltip.classList.remove('hidden');
                const triggerRect = trigger.getBoundingClientRect();
                const tooltipRect = tooltip.getBoundingClientRect();

                let top = triggerRect.bottom + GAP;
                let left = triggerRect.left;

                if (left + tooltipRect.width > window.innerWidth - 10) {
                    left = window.innerWidth - tooltipRect.width - 10;
                }
                if (left < 10) left = 10;

                if (top + tooltipRect.height > window.innerHeight - 10) {
                    top = triggerRect.top - GAP - tooltipRect.height;
                }

                tooltip.style.cssText = `top: ${top}px; left: ${left}px;`;
            }

            function show(trigger) {
                clearTimeout(timeouts.close);
                timeouts.open = setTimeout(() => {
                    activeTrigger = trigger;
                    tooltip.innerHTML = trigger.dataset.tooltipHtml || '';
                    requestAnimationFrame(() => position(trigger));
                }, DELAYS.open);
            }

            function scheduleHide(trigger) {
                clearTimeout(timeouts.open);
                timeouts.close = setTimeout(() => {
                    if (activeTrigger === trigger) hide();
                }, DELAYS.close);
            }

            function getTrigger(target) {
                return target?.closest?.('.tooltip-trigger');
            }

            document.addEventListener('pointerover', event => {
                const trigger = getTrigger(event.target);
                if (!trigger || trigger.contains(event.relatedTarget)) return;
                show(trigger);
            });

            document.addEventListener('pointerout', event => {
                const trigger = getTrigger(event.target);
                if (!trigger || trigger.contains(event.relatedTarget)) return;
                scheduleHide(trigger);
            });

            document.addEventListener('focusin', event => {
                const trigger = getTrigger(event.target);
                if (trigger) show(trigger);
            });

            document.addEventListener('focusout', event => {
                const trigger = getTrigger(event.target);
                if (trigger) scheduleHide(trigger);
            });

            ['scroll', 'resize'].forEach(event => {
                window.addEventListener(event, () => {
                    if (activeTrigger && !tooltip.classList.contains('hidden')) {
                        requestAnimationFrame(() => position(activeTrigger));
                    }
                }, {
                    passive: true
                });
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.querySelector('input[name="search"]');
            let pastSevenDays = document.querySelector('input[name="past_seven_days"]');
            let overdue = document.querySelector('input[name="overdue"]');
            let categorySelect = document.querySelector('select[name="category"]');
            let assignmentFilter = document.getElementById('assignment_filter');
            // let statusSelect = document.querySelector('select[name="status"]');
            let storeSelect = document.querySelector('select[name="store"]');
            let equipmentTableWrapper = document.querySelector('#equipment-table-wrapper');
            let equipmentStatusCheckboxes = document.querySelectorAll('input[name="equipment_status[]"]');
            let timeout = null;
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;


            const screenKey = "equipment_filters";

            const fieldMap = {
                'search': searchInput,
                'category': categorySelect,
                // 'status': statusSelect,
                'store': storeSelect,
                'equipment_status': equipmentStatusCheckboxes,
                'past_seven_days': pastSevenDays,
                'overdue': overdue,
                'assignment_filter': assignmentFilter,
            };

            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchEquipments();
                fetchSchedules();
            });

            fetchEquipments(pageParam, perPageParam); // initial fetch after loading saved filters
            function fetchEquipments(page = 1, perPage = 30) {
                const params = new URLSearchParams();
                if (searchInput.value.length >= 3 || searchInput.value === '') params.append('search', searchInput
                    .value);
                if (pastSevenDays.checked) params.append('past_seven_days', '1');
                if (overdue.checked) params.append('overdue', '1');
                if (categorySelect.value) params.append('category', categorySelect.value);
                // if (statusSelect.value) params.append('status', statusSelect.value);
                if (storeSelect.value) params.append('store', storeSelect.value);
                if (assignmentFilter && assignmentFilter.value) params.append('assignment_filter', assignmentFilter.value);
                if (perPage) params.append('per_page', perPage);
                params.append('page', page);
                // Add all checked equipment_status checkboxes
                equipmentStatusCheckboxes.forEach(cb => {
                    if (cb.checked) params.append('equipment_status[]', cb.value);
                });

                // Save current filters
                FilterFreezer.saveFilters(screenKey, fieldMap);

                // loader.classList.remove('hidden');
                equipmentTableWrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch("{{ route('admin.order-management.schedule-assignment.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        equipmentTableWrapper.innerHTML = data.html;
                        renderGroupBar(data.groups || []);
                    })
                    .catch(err => {
                        equipmentTableWrapper.innerHTML =
                            '<div class="text-red-500 p-4">Error loading equipment.</div>';
                        console.error(err);
                    })
                    .finally(() => {
                        // loader.classList.add('hidden');
                        equipmentTableWrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            let hideAssigned = document.querySelector('input[name="hide_unassigned"]');
            let unassignedOrder = document.getElementById('unassignedOrder');
            hideAssigned.checked = localStorage.getItem('hide_unassigned') === '1' ? true : false;
            if (hideAssigned.checked) {
                unassignedOrder.classList.add('hidden');
            } else {
                unassignedOrder.classList.remove('hidden');
            }
            hideAssigned.addEventListener('change', function() {
                const hideValue = this.checked ? '1' : '0';
                if (hideValue === '1') {
                    unassignedOrder.classList.add('hidden');
                } else {
                    unassignedOrder.classList.remove('hidden');
                }
                localStorage.setItem('hide_unassigned', hideValue);
            });

            // Register pagination
            Paginator.init({
                wrapper: equipmentTableWrapper,
                fetchCallback: fetchEquipments
            });

            // Delayed search
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchEquipments, 400);
            });

            // Immediate filters
            // [categorySelect, statusSelect, storeSelect].forEach(el => el.addEventListener('change', fetchEquipments));
            categorySelect.addEventListener('change', () => {
                fetchEquipments();
                fetchSchedules();
            });

            storeSelect.addEventListener('change', fetchEquipments);

            if (assignmentFilter) assignmentFilter.addEventListener('change', fetchEquipments);

            // Immediate filter for checkboxes
            equipmentStatusCheckboxes.forEach(cb => cb.addEventListener('change', fetchEquipments));

            pastSevenDays.addEventListener('change', fetchEquipments);

            overdue.addEventListener('change', fetchEquipments);

            let loadingIndicator = document.querySelector('#schedule-loading');
            let scheduleTableWrapper = document.querySelector('#schedule-table-wrapper');
            let unassignedOrderCount = document.getElementById('unassignedOrderCount');
            fetchSchedules(); // initial fetch for schedules
            function fetchSchedules() {
                const params = new URLSearchParams();
                params.append('unassigned_equipment', 1);
                if (categorySelect.value) params.append('category', categorySelect.value);
                params.append('per_page', 'all');

                // Show loader
                loadingIndicator.classList.remove('hidden');
                scheduleTableWrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.order-management.schedules.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        scheduleTableWrapper.innerHTML = response.html;
                        unassignedOrderCount.textContent = `(${response.total})`;
                    })
                    .finally(() => {
                        loadingIndicator.classList.add('hidden');
                        scheduleTableWrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }


            const modal = document.getElementById('equipmentAssignModal');

            const equipmentAssignForm = document.getElementById('equipmentAssignForm');
            const equipmentCategorySelect = document.getElementById('category_select');
            const equipmentSelect = document.getElementById('equipment_unique_id');
            const assignBtn = document.getElementById('equipment-assign-submit');
            const statusDisplayId = 'equipment-status-display';
            const equipmentPageLinkId = 'equipment-page-link';
            const orderIdLabel = document.getElementById('assign-order-id');
            const customerNameLabel = document.getElementById('assign-customer-name');
            const productNameLabel = document.getElementById('assign-product-name');

            let fullData = {}; // store categories + equipment

            let order
            // --- Event delegation for OPEN buttons (works after table refresh) ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.equipment-assign-btn');
                if (!btn) return;

                const orderProductUniqueId = btn.getAttribute('data-order-product-unique-id');
                const orderId = btn.dataset.orderId || '';
                const orderUniqueId = btn.dataset.orderUniqueId || '';
                const productName = btn.dataset.productName || '';
                const customerName = btn.dataset.customerName || '';
                let orderDetailUrl = "{{ route('admin.order-management.orders.edit', ['unique_id' => 'ORDER_ID_PLACEHOLDER']) }}";

                document.getElementById('order-product-unique-id').value = orderProductUniqueId || '';

                if (orderIdLabel) {
                    orderIdLabel.textContent = orderId ? `#${orderId.replace(/^#/, '')}` : '-';
                    orderIdLabel.href = orderUniqueId ? orderDetailUrl.replace('ORDER_ID_PLACEHOLDER', orderUniqueId) : '';
                }

                if (customerNameLabel) {
                    customerNameLabel.textContent = customerName || '-';
                }

                if (productNameLabel) {
                    productNameLabel.textContent = productName || '-';
                }

                modal.classList.remove('hidden');

                // Refresh status on open in case select kept previous state
                updateEquipmentStatus();
            });

            // --- Event delegation for CLOSE buttons (works after table refresh) ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.close-equipment-assign-modal');
                if (!btn) return;
                clearModalFields();
                modal.classList.add('hidden');
            });

            function clearModalFields() {
                const userSel = document.getElementById('user_unique_id');
                const equipSel = document.getElementById('equipment_unique_id');
                const orderInput = document.getElementById('order-product-unique-id');
                const statusDiv = document.getElementById('equipment-status-display');
                const pageLink = document.getElementById('equipment-page-link');

                if (userSel) userSel.selectedIndex = 0;
                if (equipSel) equipSel.selectedIndex = 0;
                if (orderInput) orderInput.value = '';
                if (orderIdLabel) orderIdLabel.textContent = '-';
                if (customerNameLabel) customerNameLabel.textContent = '-';
                if (productNameLabel) productNameLabel.textContent = '-';
                if (statusDiv) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'text-sm font-semibold text-gray-600';
                }
                if (pageLink) {
                    pageLink.href = '';
                    pageLink.textContent = '';
                }
                if (equipmentAssignForm) {
                    equipmentAssignForm.reset();
                }

                // Disable assign button until a valid available option is chosen
                if (assignBtn) assignBtn.disabled = true;
            }

            equipmentCategorySelect.addEventListener('change', function() {

                const selectedCatId = Number(this.value);
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';

                let equipments = [];

                // If no category selected -> load ALL equipments
                if (!selectedCatId) {
                    fullData.forEach(cat => {
                        if (Array.isArray(cat.equipments)) {
                            equipments = equipments.concat(cat.equipments);
                        }
                    });
                } else {
                    // Only selected category
                    const category = fullData.find(c => c.id === selectedCatId);
                    if (!category) return;
                    equipments = category.equipments || [];
                }

                //  If category has NO equipment
                if (equipments.length === 0) {
                    const opt = document.createElement('option');
                    opt.textContent = 'No equipments';
                    opt.disabled = true;
                    opt.selected = true;
                    equipmentSelect.appendChild(opt);

                    updateEquipmentStatus();
                    return;
                }

                // Group by status
                const groups = {
                    maintenance: [],
                    rented: [],


                    damaged: [],
                    available: [],
                    other: []
                };

                equipments.forEach(equipment => {
                    const status = (equipment.current_status || '').toLowerCase();

                    if (status === 'available') groups.available.push(equipment);
                    else if (status === 'rented') groups.rented.push(equipment);
                    else if (status === 'damaged') groups.damaged.push(equipment);
                    else if (status === 'maintenance') groups.maintenance.push(equipment);
                    else groups.other.push(equipment);
                });



                // Append groups
                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);

                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);


                updateEquipmentStatus();
            });

            // Update status and button
            function updateEquipmentStatus() {
                const statusDiv = document.getElementById(statusDisplayId);
                const pageLink = document.getElementById(equipmentPageLinkId);

                if (!equipmentSelect || !statusDiv || !assignBtn || !pageLink) return;

                const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex] || {};
                const status = selectedOption.getAttribute?.('data-current-status');
                const link = selectedOption.getAttribute?.('data-link') || '';
                const title = selectedOption.getAttribute?.('data-link-title') || '';

                if (!status) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'mt-2 text-sm font-semibold text-gray-600';
                    assignBtn.disabled = true;
                    pageLink.href = '';
                    pageLink.textContent = '';
                    return;
                }

                let statusText = '';
                let statusColor = 'text-gray-600';
                let isAvailable = true;

                switch (status) {
                    case 'available':
                        statusText = 'Available';
                        statusColor = 'text-green-600';
                        isAvailable = true;
                        break;

                    case 'rented':
                        statusText = 'Rented';
                        statusColor = 'text-gray-600';
                        isAvailable = true;

                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently Rented, so it cannot be assigned to this Order.",
                        //     "Rented "
                        // );
                        // equipmentSelect.selectedIndex = 0;
                        //return;
                        break;


                    case 'damaged':
                        statusText = 'Not Available';
                        statusColor = 'text-red-600';
                        isAvailable = true;
                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently marked as Damaged, do you want to automatically change the status to Available and assign to this order?",
                        //     "Damaged "
                        // );
                        break;
                    case 'maintenance':
                        statusText = 'Maint. Hold';
                        statusColor = 'text-yellow-600';
                        isAvailable = true;
                        // SweetAlert message for rented items
                        // window.showError(
                        //     "This item is currently marked as Maint. Hold, do you want to automatically change the status to Available and assign to this order?",
                        //     "Maint. Hold "
                        // );
                        break;
                    default:
                        statusText = status || '';
                        statusColor = 'text-gray-600';
                        isAvailable = true;
                }

                statusDiv.textContent = `Status: ${statusText}`;
                statusDiv.className = `mt-2 text-sm font-semibold ${statusColor}`;
                assignBtn.disabled = !isAvailable;
                pageLink.href = link;
                pageLink.textContent = title;
            }

            // Static elements inside the modal can use normal listeners
            if (equipmentSelect) {
                equipmentSelect.addEventListener('change', updateEquipmentStatus);
                // Initial status update
                updateEquipmentStatus();
            }

            // Form submit
            equipmentAssignForm?.addEventListener('submit', function(e) {
                e.preventDefault();

                if (window.$ && $(equipmentAssignForm).parsley && !$(equipmentAssignForm).parsley()
                    .isValid()) {
                    $(equipmentAssignForm).parsley().validate();
                    return;
                }

                const submitBtn = document.getElementById('equipment-assign-submit');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Assigning...';
                }

                const formData = new FormData(equipmentAssignForm);

                apiFetch('{{ route('admin.order-management.schedules.assign-equipment') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                ?.getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: formData
                    })
                    .then(data => {
                        if (data?.success) {
                            modal.classList.add('hidden');
                            if (window.notyf) notyf.success(data.message);
                            clearModalFields();
                            fetchEquipment();
                            fetchSchedules();
                            fetchEquipments();
                        } else {
                            if (window.notyf) notyf.error(data?.message || 'Something went wrong.');
                        }
                    })
                    .finally(() => {
                        if (submitBtn) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = 'Assign';
                        }
                    });
            });

            fetchEquipment(); // initial fetch without loading all equipments

            const groupBar = document.getElementById('equipment-group-bar');
            const groupBarInner = groupBar?.querySelector('div');

            function renderGroupBar(groups) {
                if (!groupBar || !groupBarInner) return;

                if (!groups || groups.length === 0) {
                    groupBar.classList.add('hidden');
                    groupBarInner.innerHTML = '';
                    return;
                }

                const circle = (count, bg, text, title) =>
                    count > 0
                        ? `<span title="${title}" style="width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;font-size:11px;font-weight:700;flex-shrink:0;" class="${bg} ${text}">${count}</span>`
                        : '';

                groupBarInner.innerHTML = groups.map(g => `
                    <div class="flex-shrink-0 inline-flex items-center gap-1.5 rounded-md border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-700 whitespace-nowrap shadow-sm">
                        <span>${g.name}</span>
                        ${circle(g.available,   'bg-green-100',  'text-green-700',  'Available')}
                        ${circle(g.rented,      'bg-blue-100',   'text-blue-700',   'Rented')}
                        ${circle(g.maintenance, 'bg-yellow-100', 'text-yellow-700', 'Maint. Hold')}
                        ${circle(g.damaged,     'bg-red-100',    'text-red-700',    'Damaged')}
                        <span title="Total" style="width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;border-radius:50%;font-size:11px;font-weight:700;flex-shrink:0;" class="bg-gray-200 text-gray-600">${g.total}</span>
                    </div>`).join('');

                groupBar.classList.remove('hidden');
            }


            function fetchEquipment(loadAll = true) {
                apiFetch('{{ route('admin.maintenance-management.equipment.fetch-with-categories') }}')
                    .then(data => {
                        if (data?.success) {

                            fullData = data.categories; // store full categories

                            equipmentCategorySelect.innerHTML = '<option value="">Select Category</option>';

                            data.categories.forEach(cat => {
                                const option = document.createElement('option');
                                option.value = cat.id;
                                option.textContent = cat.title;
                                equipmentCategorySelect.appendChild(option);
                            });

                            //  Load all equipment immediately after data arrives
                            loadAllEquipments();
                        }
                    });
            }


            function loadAllEquipments() {
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';

                let equipments = [];

                fullData.forEach(cat => {
                    if (Array.isArray(cat.equipments)) {
                        equipments = equipments.concat(cat.equipments);
                    }
                });

                // Group by status
                const groups = {
                    available: [],
                    rented: [],
                    damaged: [],
                    maintenance: [],
                    other: []
                };

                equipments.forEach(equipment => {
                    const status = (equipment.current_status || '').toLowerCase();

                    if (status === 'available') groups.available.push(equipment);
                    else if (status === 'rented') groups.rented.push(equipment);
                    else if (status === 'damaged') groups.damaged.push(equipment);
                    else if (status === 'maintenance') groups.maintenance.push(equipment);
                    else groups.other.push(equipment);
                });


                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);
                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);


                updateEquipmentStatus();
            }

            function appendGroup(label, list) {
                if (list.length === 0) return;

                const group = document.createElement('optgroup');
                group.label = label;

                list.forEach(equipment => {
                    const opt = document.createElement('option');
                    opt.value = equipment.unique_id;
                    opt.textContent = equipment.equipment_name + " || " + equipment.equipment_id;
                    opt.setAttribute('data-current-status', equipment.current_status || '');
                    opt.setAttribute('data-link', equipment.link || '');
                    opt.setAttribute('data-link-title', equipment.link_title || '');
                    group.appendChild(opt);
                });

                equipmentSelect.appendChild(group);
            }
        });



        document.getElementById('reloadBtn').addEventListener('click', function(e) {
            const icon = document.getElementById('reloadIcon');
            icon.classList.add('animate-spin'); // Tailwind's spin animation

            // allow spin to show before reload
            setTimeout(() => {
                window.location.reload();
            }, 200); // slight delay so user sees the spin
        });
    </script>
@endpush
