@extends('admin.layouts.app')

@section('title', 'Dispatch')

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
            border-top: 1px solid rgb(229 231 235);
            transform: rotate(45deg);
        }
    </style>
@endpush

@section('content')

    @include('flash::message')

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-4">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-truck class="w-6 h-6 text-blue-600" />
            Dispatch Management
        </h1>
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.order-management.dispatch.ai-rules.index') }}"
                class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-lg font-medium text-sm">
                <x-heroicon-o-cpu-chip class="w-4 h-4" />
                Dispatch AI Rules
            </a>
            <a href="{{ route('admin.order-management.dispatch.index') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg font-medium text-md flex items-center gap-2">
                <x-heroicon-o-arrow-path class="w-5 h-5" />
                Reload
            </a>
        </div>
    </div>

    {{-- ===== Driver Workload Summary ===== --}}
    <div id="driver-cards-wrapper" class="mb-4">
        @include('admin.order_management.dispatch.partials._driver_cards', ['driverCards' => $driverCards, 'showAll' => $showAll ?? false])
    </div>

    {{-- ===== AI Draft Panel ===== --}}
    @include('admin.order_management.dispatch.partials._ai_draft', ['latestDraft' => $latestDraft ?? null])

    <script>
    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.dispatch-card-jump');
        if (!btn) return;
        const orderNumber = btn.dataset.orderNumber;
        if (!orderNumber) return;

        // Clear all text search inputs
        ['customer_name', 'customer_company_name', 'customer_phone'].forEach(name => {
            const el = document.querySelector(`input[name="${name}"]`);
            if (el) el.value = '';
        });

        // Reset all select filters to their default "All ..." state
        ['select[name="category"]', '#payment_method', '#payment_status', '#date_filter', '#driver_filter'].forEach(sel => {
            const el = document.querySelector(sel);
            if (el) el.value = '';
        });

        // Force Schedule Type: Delivery + Return both checked
        document.querySelectorAll('input[name="schedule_type[]"]').forEach(cb => { cb.checked = true; });

        // Force all store locations checked
        document.querySelectorAll('input[name="store_location[]"]').forEach(cb => { cb.checked = true; });

        // Set the order number filter
        const orderInput = document.querySelector('input[name="order_number"]');
        if (orderInput) orderInput.value = orderNumber;

        // Run the search (window.fetchDispatch is exposed by the DOMContentLoaded block)
        if (typeof window.fetchDispatch === 'function') {
            window.fetchDispatch();
        }

        // Scroll to the dispatch table
        setTimeout(() => {
            const table = document.getElementById('dispatch-table-wrapper');
            if (table) table.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }, 200);
    });
    </script>

    <div class="bg-white p-4 rounded-xl shadow-sm space-y-4">
        <!-- Row 1: Search Inputs -->
        <div class="flex flex-wrap gap-4 items-center">
            <div class="w-full sm:w-auto">
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                    Clear
                </button>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_name" placeholder="Customer name" name="customer_name"
                        value="{{ request('customer_name') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_company_name" placeholder="Customer company"
                        name="customer_company_name" value="{{ request('customer_company_name') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div>
                <div class="relative bg-white">
                    <input type="text" id="customer_phone" placeholder="(xxx) xxx-xxxx" name="customer_phone"
                        value="{{ request('customer_phone') }}"
                        class="masked-phone pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-38 focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-35">
                <div class="relative bg-white">
                    <input type="text" id="order_number" placeholder="Order ID" name="order_number"
                        value="{{ request('order_number') }}"
                        class="pl-3 pr-10 py-3 px-3 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <select name="category"
                    class="choices-select py-3 px-3 w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">Select Category</option>
                    @foreach ($categories as $id => $category)
                        <option value="{{ $id }}" @selected(request('category') == $id)>
                            {{ $category }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <select id="payment_method" name="payment_method"
                    class="border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-38 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payment Types</option>
                    <option value="Card" @selected(request('payment_method') == 'Card')>Card</option>
                    <option value="COD" @selected(request('payment_method') == 'COD')>POD</option>
                    <option value="Account" @selected(request('payment_method') == 'Account')>Account</option>
                </select>
            </div>

            <div>
                <select id="payment_status" name="payment_status"
                    class="border bg-white border-gray-300 rounded-md py-3 px-3 text-sm w-34 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payments</option>
                    <option value="Paid" @selected(request('payment_status') == 'Paid')>Paid</option>
                    <option value="Pending" @selected(request('payment_status') == 'Pending')>Pending</option>
                    <option value="Account" @selected(request('payment_status') == 'Account')>Account</option>
                    <option value="Partial Refund" @selected(request('payment_status') == 'Partial Refund')>Partial Refund</option>
                    <option value="Refunded" @selected(request('payment_status') == 'Refunded')>Refunded</option>
                    <option value="Failed" @selected(request('payment_status') == 'Failed')>Failed</option>
                </select>
            </div>

            <div>
                <select name="date_filter" id="date_filter"
                    class="border border-gray-300 rounded-md py-3 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">All Dates</option>
                    <option value="today" @selected(request('date_filter') == 'today')>Today</option>
                    <option value="week" @selected(request('date_filter') == 'week')>This Week</option>
                    <option value="month" @selected(request('date_filter') == 'month')>This Month</option>
                </select>
            </div>

            <div>
                <select name="driver_id" id="driver_filter"
                    class="border border-gray-300 rounded-md py-3 px-3 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500">
                    <option value="">All Drivers</option>
                    @foreach ($driverEmployees as $driverId => $driverName)
                        <option value="{{ $driverId }}">{{ $driverName }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @php
            $scheduleType  = request()->input('schedule_type');
            $storeLocation = request()->input('store_location');
        @endphp

        <!-- Row 2: Checkboxes -->
        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
            <div class="flex flex-wrap items-center gap-6">

                <!-- Schedule Type: Delivery / Return -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-calendar class="w-5 h-5 text-blue-500" />
                    <span class="font-medium">Schedule Type</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Delivery" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            @checked(is_array($scheduleType) ? in_array('Delivery', $scheduleType) : true)>
                        Delivery
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Return" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            @checked(is_array($scheduleType) ? in_array('Return', $scheduleType) : true)>
                        Return
                    </label>
                </div>

                <!-- Store Locations -->
                <div class="flex items-center gap-2 bg-white rounded-md px-4 py-2 border">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-purple-500" />
                    <span class="font-medium">Store</span>
                    @foreach ($stores as $store)
                        <label class="flex items-center gap-1 {{ $loop->first ? 'ml-2' : '' }}">
                            <input type="checkbox" name="store_location[]" value="{{ $store->id }}"
                                id="store_location_{{ Str::slug($store->store_name, '_') }}"
                                class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                                @checked(is_array($storeLocation) ? in_array($store->id, $storeLocation) : true)>
                            {{ $store->store_name }}
                        </label>
                    @endforeach
                </div>

                <!-- Quick Filter Buttons (Truck only) -->
                <div class="flex gap-2">
                    <button type="button"
                        class="dispatch-filter-btn px-3 py-2 rounded bg-blue-100 text-blue-700 text-xs font-semibold hover:bg-blue-200 border border-blue-200"
                        data-schedule-type="Delivery">Deliveries - Truck</button>
                    <button type="button"
                        class="dispatch-filter-btn px-3 py-2 rounded bg-purple-100 text-purple-700 text-xs font-semibold hover:bg-purple-200 border border-purple-200"
                        data-schedule-type="Return">Returns - Truck</button>
                </div>

            </div>
        </div>
    </div>

    <div class="mx-auto py-6">
        <div id="dispatch-table-wrapper">
            @include('admin.order_management.dispatch.partials._table', [
                'orderProducts' => [],
            ])
        </div>
    </div>

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
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
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 space-y-2">
                    <div>
                        <span class="text-xs font-semibold text-blue-700">Order ID:</span>
                        <a id="assign-order-id" href="#" class="text-sm text-blue-900 font-semibold">-</a>
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
                        <a href="#" class="text-blue-600 hover:underline text-sm font-semibold" id="equipment-page-link"></a>
                    </div>
                </div>
                <input type="hidden" id="order-product-unique-id" name="order_product_unique_id" value="">
            </div>

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

    {{-- ===== Driver Assignment Modal ===== --}}
    <div id="driverAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center px-4">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">

            <!-- Header -->
            <div class="relative px-6 pt-6 pb-4 border-b">
                <h2 id="driver-modal-title" class="text-xl font-semibold text-gray-900 text-center">Assign Driver / Tech</h2>
                <button type="button" id="close-driver-modal"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none absolute right-6 top-6">&times;</button>
            </div>

            <!-- Order Summary -->
            <div class="px-6 pt-4 pb-2">
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-1.5 text-sm">
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Order:</span>
                        <span id="driver-modal-order-number" class="font-semibold text-gray-800">-</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Customer:</span>
                        <span id="driver-modal-customer" class="text-gray-800">-</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Product:</span>
                        <span id="driver-modal-product" class="text-gray-800">-</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Date:</span>
                        <span id="driver-modal-date" class="text-gray-800">-</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Type:</span>
                        <span id="driver-modal-slot-label"
                            class="inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700">-</span>
                    </div>
                    <div class="flex gap-2">
                        <span class="font-semibold text-gray-500 w-24 shrink-0">Driver Phone:</span>
                        <a id="driver-modal-phone" href="#" class="text-gray-800 hover:underline">-</a>
                    </div>
                </div>
            </div>

            <!-- Driver Select -->
            <div class="px-6 pt-4 pb-3">
                <label class="block text-sm font-medium text-gray-700 mb-1" for="driver-select">
                    Driver / Tech
                </label>
                <select id="driver-select"
                    class="w-full border border-gray-300 rounded-md px-3 py-3 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
                    <option value="">— Unassign / Clear —</option>
                    @foreach ($driverEmployees as $driverId => $driverName)
                        <option value="{{ $driverId }}">{{ $driverName }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Dispatch Date Override -->
            <div id="driver-modal-dispatch-section" class="px-6 pb-3">
                <div class="rounded-lg border border-gray-100 bg-gray-50 p-3 space-y-2 text-xs">
                    <div class="flex items-center gap-2">
                        <span id="driver-modal-rental-label" class="font-semibold text-gray-500 w-32 shrink-0">Rental Start:</span>
                        <span id="driver-modal-rental-date-display" class="text-gray-700">-</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <label id="driver-modal-dispatch-label" for="driver-dispatch-date-input"
                            class="font-semibold text-gray-500 w-32 shrink-0 cursor-pointer">Dispatch Delivery:</label>
                        <div class="flex items-center gap-2">
                            <div class="relative">
                                <input type="date" id="driver-dispatch-date-input"
                                    placeholder="Select Date"
                                    class="border border-gray-300 rounded px-3 py-1.5 text-xs pr-8 w-40 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer">
                                <div class="pointer-events-none absolute inset-y-0 right-2 flex items-center">
                                    <x-heroicon-o-calendar class="w-4 h-4 text-gray-400" />
                                </div>
                            </div>
                            <button type="button" id="driver-dispatch-date-clear"
                                class="text-gray-400 hover:text-red-500 text-xs hidden" title="Clear dispatch date">✕ Clear</button>
                            <span id="driver-dispatch-date-badge" class="hidden text-[9px] font-bold px-1.5 py-0.5 rounded"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Also assign checkbox -->
            <div class="px-6 pb-4">
                <label class="flex items-start gap-2 text-sm text-gray-700 cursor-pointer select-none">
                    <input type="checkbox" id="driver-modal-also-assign"
                        class="mt-0.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" checked>
                    <span id="driver-modal-also-assign-label">Also assign same Driver / Tech to return/pickup</span>
                </label>
                <p id="driver-modal-other-driver-note" class="text-xs text-amber-700 mt-1 ml-5 hidden"></p>
                <p id="driver-modal-delivery-locked-note" class="text-xs text-gray-400 mt-1 ml-5 hidden">Delivery already completed — delivery driver is locked.</p>
            </div>

            <!-- Hidden state -->
            <input type="hidden" id="driver-modal-slot" value="">
            <input type="hidden" id="driver-modal-order-product-uid" value="">
            <input type="hidden" id="driver-modal-order-uid" value="">
            <input type="hidden" id="driver-modal-other-slot-driver-id" value="">
            <input type="hidden" id="driver-modal-other-slot-driver-name" value="">
            <input type="hidden" id="driver-modal-original-dispatch-date" value="">
            <input type="hidden" id="driver-modal-rental-delivery-date-raw" value="">
            <input type="hidden" id="driver-modal-rental-return-date-raw" value="">

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" id="close-driver-modal-footer"
                    class="px-6 py-3 rounded-lg font-medium text-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="button" id="driver-assign-submit"
                    class="px-6 py-3 rounded-lg font-medium text-md bg-blue-600 text-white hover:bg-blue-700 shadow-sm transition">
                    Save Driver
                </button>
            </div>
        </div>
    </div>

    <x-admin.equipment-store-modal :stores="$storesForModal" />

@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.__scheduleTooltipInitialized) return;
            window.__scheduleTooltipInitialized = true;

            const tooltip = document.createElement('div');
            tooltip.className =
                'fixed hidden rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs text-gray-700 shadow-lg max-w-xs z-[9999]';
            document.body.appendChild(tooltip);

            let activeTrigger = null;
            let timeouts = { open: null, close: null };
            const DELAYS = { open: 120, close: 80 };
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
                const triggerRect  = trigger.getBoundingClientRect();
                const tooltipRect  = tooltip.getBoundingClientRect();
                let top  = triggerRect.bottom + GAP;
                let left = triggerRect.left;
                if (left + tooltipRect.width > window.innerWidth - 10) left = window.innerWidth - tooltipRect.width - 10;
                if (left < 10) left = 10;
                if (top + tooltipRect.height > window.innerHeight - 10) top = triggerRect.top - GAP - tooltipRect.height;
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

            document.addEventListener('pointerover', e => {
                const trigger = getTrigger(e.target);
                if (!trigger || trigger.contains(e.relatedTarget)) return;
                show(trigger);
            });
            document.addEventListener('pointerout', e => {
                const trigger = getTrigger(e.target);
                if (!trigger || trigger.contains(e.relatedTarget)) return;
                scheduleHide(trigger);
            });
            document.addEventListener('focusin', e => {
                const trigger = getTrigger(e.target);
                if (trigger) show(trigger);
            });
            document.addEventListener('focusout', e => {
                const trigger = getTrigger(e.target);
                if (trigger) scheduleHide(trigger);
            });
            ['scroll', 'resize'].forEach(evt => {
                window.addEventListener(evt, () => {
                    if (activeTrigger && !tooltip.classList.contains('hidden')) {
                        requestAnimationFrame(() => position(activeTrigger));
                    }
                }, { passive: true });
            });
        });
    </script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const customerNameInput        = document.querySelector('input[name="customer_name"]');
            const customerCompanyNameInput = document.querySelector('input[name="customer_company_name"]');
            const customerPhoneInput       = document.querySelector('input[name="customer_phone"]');
            const orderNumberInput         = document.querySelector('input[name="order_number"]');
            const categoryInput            = document.querySelector('select[name="category"]');
            const paymentStatusInput       = document.querySelector('select[name="payment_status"]');
            const paymentMethodInput       = document.querySelector('select[name="payment_method"]');
            const dateFilterInput          = document.querySelector('select[name="date_filter"]');
            const driverFilterInput        = document.getElementById('driver_filter');
            const storeLocationInputs      = document.querySelectorAll('input[name="store_location[]"]');
            const scheduleTypeInputs       = document.querySelectorAll('input[name="schedule_type[]"]');
            const loadingIndicator         = document.querySelector('#dispatch-loading');
            const wrapper                  = document.querySelector('#dispatch-table-wrapper');

            let timeout = null;

            const screenKey = 'dispatch_filters';

            const fieldMap = {
                'customer_name':        customerNameInput,
                'customer_company_name':customerCompanyNameInput,
                'customer_phone':       customerPhoneInput,
                'order_number':         orderNumberInput,
                'category':             categoryInput,
                'payment_status':       paymentStatusInput,
                'payment_method':       paymentMethodInput,
                'date_filter':          dateFilterInput,
                'driver_id':            driverFilterInput,
                'store_location[]':     storeLocationInputs,
                'schedule_type[]':      scheduleTypeInputs,
            };

            // Clear filters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                scheduleTypeInputs.forEach(input => { input.checked = true; });
                storeLocationInputs.forEach(input => { input.checked = true; });
                if (dateFilterInput) dateFilterInput.value = '';
                // Reset driver filter
                if (driverFilterInput) driverFilterInput.value = '';
                fetchDispatch();
            });

            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            // Default date_filter: All Dates when no saved preference exists
            if (dateFilterInput && !dateFilterInput.value) {
                dateFilterInput.value = '';
            }

            const pageParam    = new URLSearchParams(window.location.search).get('page') || 1;
            const perPageParam = document.getElementById('per_page_sm')?.value ||
                                 new URLSearchParams(location.search).get('per_page') || null;

            fetchDispatch(pageParam, perPageParam);

            // Expose globally so driver modal JS can call it after saving a driver
            window.fetchDispatch = fetchDispatch;

            // Apply saved card view mode and show-assigned filter on page load
            const savedDaf = localStorage.getItem('driver_assign_filter') || 'today';
            if (savedDaf === 'all') {
                // Server-rendered cards default to Today Only; re-fetch to match stored preference
                setTimeout(() => window.refreshDriverCards(), 50);
            } else {
                setTimeout(() => applyDriverCardMode(localStorage.getItem('driver_card_view') || 'separate'), 0);
            }

            // Refresh driver workload cards without reloading the page
            window.refreshDriverCards = function (targetMode) {
                const wrapper = document.getElementById('driver-cards-wrapper');
                if (!wrapper) return;
                wrapper.classList.add('opacity-50');
                const showAll = localStorage.getItem('driver_assign_filter') === 'all';
                const cardsUrl = "{{ route('admin.order-management.dispatch.driver-cards') }}" + (showAll ? '?show_all=1' : '');
                apiFetch(cardsUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(data => {
                    if (data?.html) {
                        wrapper.innerHTML = data.html;
                        // Re-apply the current card view mode after the DOM is replaced
                        const mode = targetMode || localStorage.getItem('driver_card_view') || 'separate';
                        applyDriverCardMode(mode);
                    }
                })
                .finally(() => wrapper.classList.remove('opacity-50'));
            };

            // Switch Show Assigned filter (Today Only / All) and re-fetch driver cards
            function applyDriverAssignFilter(filter) {
                localStorage.setItem('driver_assign_filter', filter);
                window.refreshDriverCards();
            }

            // Apply card view mode (separate/combined) — works on freshly injected DOM too
            function applyDriverCardMode(mode) {
                const container = document.getElementById('driver-cards-container');
                const btnSep  = document.getElementById('dcv-separate');
                const btnComb = document.getElementById('dcv-combined');
                if (!container) return;

                container.querySelectorAll('.driver-card-separate').forEach(el => el.classList.toggle('hidden', mode !== 'separate'));
                container.querySelectorAll('.driver-card-combined').forEach(el => el.classList.toggle('hidden', mode !== 'combined'));

                if (btnSep && btnComb) {
                    const isSep = mode === 'separate';
                    btnSep.classList.toggle('bg-blue-600', isSep);
                    btnSep.classList.toggle('text-white',  isSep);
                    btnSep.classList.toggle('bg-white',   !isSep);
                    btnSep.classList.toggle('text-gray-600', !isSep);
                    btnComb.classList.toggle('bg-blue-600', !isSep);
                    btnComb.classList.toggle('text-white',  !isSep);
                    btnComb.classList.toggle('bg-white',    isSep);
                    btnComb.classList.toggle('text-gray-600', isSep);
                }

                localStorage.setItem('driver_card_view', mode);
            }

            // Persistent event delegation for all driver card toggles (survives innerHTML replacement)
            document.addEventListener('click', function (e) {
                if (e.target.closest('#dcv-separate')) {
                    applyDriverCardMode('separate');
                    return;
                }
                if (e.target.closest('#dcv-combined')) {
                    window.refreshDriverCards('combined');
                    return;
                }
                if (e.target.closest('#daf-all')) {
                    applyDriverAssignFilter('all');
                    return;
                }
                if (e.target.closest('#daf-today')) {
                    applyDriverAssignFilter('today');
                    return;
                }

                // View All / Collapse for driver cards
                const viewAllBtn = e.target.closest('.dc-view-all-btn');
                if (viewAllBtn) {
                    const card = viewAllBtn.closest('[data-driver-card]');
                    if (!card) return;
                    const sections   = card.querySelectorAll('.dc-scroll-section');
                    const isExpanded = card.dataset.expanded === 'true';
                    if (isExpanded) {
                        sections.forEach(s => { s.style.maxHeight = '13rem'; s.style.overflowY = 'auto'; });
                        card.dataset.expanded  = 'false';
                        viewAllBtn.textContent = 'View All';
                    } else {
                        sections.forEach(s => { s.style.maxHeight = ''; s.style.overflowY = ''; });
                        card.dataset.expanded  = 'true';
                        viewAllBtn.textContent = 'Collapse';
                    }
                    return;
                }

                // Update button: sort both delivery and return columns of this card by priority
                const updateBtn = e.target.closest('.dispatch-card-update-btn');
                if (!updateBtn) return;

                const card = updateBtn.closest('.bg-white.rounded-xl');
                if (!card) return;

                const separateView = card.querySelector('.driver-card-separate');
                if (!separateView) return;

                separateView.querySelectorAll('.flex-1.p-4').forEach(column => {
                    // Job entries live inside the .dc-scroll-section wrapper
                    const scrollSection = column.querySelector('.dc-scroll-section');
                    const container     = scrollSection || column;
                    const entries       = Array.from(container.children).filter(el => el.tagName === 'DIV');
                    if (entries.length < 2) return;

                    entries.sort((a, b) => {
                        const ba = a.querySelector('.dispatch-priority-badge');
                        const bb = b.querySelector('.dispatch-priority-badge');
                        const pa = (ba && ba.dataset.priority !== '') ? parseInt(ba.dataset.priority) : 9999;
                        const pb = (bb && bb.dataset.priority !== '') ? parseInt(bb.dataset.priority) : 9999;
                        return pa - pb;
                    });

                    entries.forEach(el => container.appendChild(el));
                });

                // Brief success feedback on the button
                const orig = updateBtn.textContent;
                updateBtn.textContent = '✓ Sorted';
                updateBtn.classList.replace('bg-blue-600', 'bg-green-600');
                setTimeout(() => {
                    updateBtn.textContent = orig;
                    updateBtn.classList.replace('bg-green-600', 'bg-blue-600');
                }, 1500);
            });

            function fetchDispatch(page = 1, perPage = 30) {
                const params = new URLSearchParams();

                if (customerNameInput && (customerNameInput.value.length >= 3 || customerNameInput.value.length === 0))
                    params.append('customer_name', customerNameInput.value);
                if (customerCompanyNameInput && (customerCompanyNameInput.value.length >= 3 || customerCompanyNameInput.value.length === 0))
                    params.append('customer_company_name', customerCompanyNameInput.value);
                if (customerPhoneInput && (customerPhoneInput.value.length >= 3 || customerPhoneInput.value.length === 0))
                    params.append('customer_phone', customerPhoneInput.value);
                if (orderNumberInput && (orderNumberInput.value.length >= 1 || orderNumberInput.value.length === 0))
                    params.append('order_number', orderNumberInput.value);
                if (categoryInput && categoryInput.value) params.append('category', categoryInput.value);
                if (paymentStatusInput && paymentStatusInput.value) params.append('payment_status', paymentStatusInput.value);
                if (paymentMethodInput && paymentMethodInput.value) params.append('payment_method', paymentMethodInput.value);
                if (dateFilterInput && dateFilterInput.value) params.append('date_filter', dateFilterInput.value);
                if (driverFilterInput && driverFilterInput.value) params.append('driver_id', driverFilterInput.value);
                if (perPage) params.append('per_page', perPage);
                params.set('page', page);

                // Schedule type checkboxes
                let anyScheduleTypeChecked = false;
                scheduleTypeInputs.forEach(input => {
                    if (input.checked) {
                        params.append('schedule_type[]', input.value);
                        anyScheduleTypeChecked = true;
                    }
                });
                if (!anyScheduleTypeChecked) params.append('schedule_type[]', false);

                // Store location checkboxes
                let anyStoreLocationChecked = false;
                storeLocationInputs.forEach(input => {
                    if (input.checked) {
                        params.append('store_location[]', input.value);
                        anyStoreLocationChecked = true;
                    }
                });
                if (!anyStoreLocationChecked) params.append('store_location[]', false);

                if (loadingIndicator) loadingIndicator.classList.remove('hidden');
                FilterFreezer.saveFilters(screenKey, fieldMap);
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.order-management.dispatch.index') }}?" + params.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    wrapper.innerHTML = response.html;
                })
                .finally(() => {
                    if (loadingIndicator) loadingIndicator.classList.add('hidden');
                    wrapper.classList.remove('opacity-50', 'pointer-events-none');
                });
            }

            // Pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchDispatch
            });

            // Input listeners
            if (customerNameInput) customerNameInput.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(fetchDispatch, 400); });
            if (customerCompanyNameInput) customerCompanyNameInput.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(fetchDispatch, 400); });
            if (customerPhoneInput) customerPhoneInput.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(fetchDispatch, 400); });
            if (orderNumberInput) orderNumberInput.addEventListener('input', () => { clearTimeout(timeout); timeout = setTimeout(fetchDispatch, 400); });
            if (categoryInput) categoryInput.addEventListener('change', fetchDispatch);
            if (paymentStatusInput) paymentStatusInput.addEventListener('change', fetchDispatch);
            if (paymentMethodInput) paymentMethodInput.addEventListener('change', fetchDispatch);
            if (dateFilterInput) dateFilterInput.addEventListener('change', fetchDispatch);
            if (driverFilterInput) driverFilterInput.addEventListener('change', fetchDispatch);
            storeLocationInputs.forEach(input => input.addEventListener('change', fetchDispatch));

            scheduleTypeInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const checked = Array.from(scheduleTypeInputs).filter(i => i.checked);
                    if (checked.length === 0) {
                        e.preventDefault();
                        input.checked = true;
                    } else {
                        fetchDispatch();
                    }
                });
            });

            // Quick filter buttons (Truck only)
            document.querySelectorAll('.dispatch-filter-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const scheduleType = this.getAttribute('data-schedule-type');

                    // Set schedule type checkboxes
                    document.querySelectorAll('input[name="schedule_type[]"]').forEach(cb => {
                        cb.checked = (cb.value === scheduleType);
                    });

                    // Check all stores
                    storeLocationInputs.forEach(cb => { cb.checked = true; });

                    fetchDispatch(pageParam, perPageParam);
                });
            });

            // ===== Equipment Assign Modal =====
            const modal              = document.getElementById('equipmentAssignModal');
            const equipmentAssignForm= document.getElementById('equipmentAssignForm');
            const categorySelect     = document.getElementById('category_select');
            const equipmentSelect    = document.getElementById('equipment_unique_id');
            const assignBtn          = document.getElementById('equipment-assign-submit');
            const orderIdLabel       = document.getElementById('assign-order-id');
            const customerNameLabel  = document.getElementById('assign-customer-name');
            const productNameLabel   = document.getElementById('assign-product-name');
            let pendingPreferredCategoryId = '';
            let fullData = {};

            function applyPreferredCategory(categoryId) {
                const normalizedCategoryId = String(categoryId || '').trim();
                if (!normalizedCategoryId || !categorySelect) { pendingPreferredCategoryId = ''; return; }
                const hasOption = Array.from(categorySelect.options).some(opt => opt.value === normalizedCategoryId);
                if (!hasOption) { pendingPreferredCategoryId = normalizedCategoryId; return; }
                pendingPreferredCategoryId = '';
                categorySelect.value = normalizedCategoryId;
                categorySelect.dispatchEvent(new Event('change'));
            }

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.equipment-assign-btn');
                if (!btn) return;

                const orderProductUniqueId = btn.getAttribute('data-order-product-unique-id');
                const orderId              = btn.dataset.orderId || '';
                const orderUniqueId        = btn.dataset.orderUniqueId || '';
                const productName          = btn.dataset.productName || '';
                const customerName         = btn.dataset.customerName || '';
                const preferredCategoryId  = btn.dataset.categoryId || '';
                const orderDetailUrl       = "{{ route('admin.order-management.orders.edit', ['unique_id' => 'ORDER_ID_PLACEHOLDER']) }}";

                document.getElementById('order-product-unique-id').value = orderProductUniqueId || '';
                if (orderIdLabel) {
                    orderIdLabel.textContent = orderId ? `#${orderId.replace(/^#/, '')}` : '-';
                    orderIdLabel.href = orderUniqueId ? orderDetailUrl.replace('ORDER_ID_PLACEHOLDER', orderUniqueId) : '';
                }
                if (customerNameLabel) customerNameLabel.textContent = customerName || '-';
                if (productNameLabel)  productNameLabel.textContent  = productName  || '-';

                modal.classList.remove('hidden');
                applyPreferredCategory(preferredCategoryId);
                updateEquipmentStatus();
            });

            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.close-equipment-assign-modal');
                if (!btn) return;
                clearModalFields();
                modal.classList.add('hidden');
            });

            function clearModalFields() {
                const userSel    = document.getElementById('user_unique_id');
                const equipSel   = document.getElementById('equipment_unique_id');
                const orderInput = document.getElementById('order-product-unique-id');
                const statusDiv  = document.getElementById('equipment-status-display');
                const pageLink   = document.getElementById('equipment-page-link');
                if (userSel)    userSel.selectedIndex    = 0;
                if (equipSel)   equipSel.selectedIndex   = 0;
                if (orderInput) orderInput.value         = '';
                if (orderIdLabel)      { orderIdLabel.textContent = '-'; orderIdLabel.href = ''; }
                if (customerNameLabel) customerNameLabel.textContent = '-';
                if (productNameLabel)  productNameLabel.textContent  = '-';
                if (statusDiv)  { statusDiv.textContent = ''; statusDiv.className = 'text-sm font-semibold text-gray-600'; }
                if (pageLink)   { pageLink.href = ''; pageLink.textContent = ''; }
                if (equipmentAssignForm) equipmentAssignForm.reset();
                pendingPreferredCategoryId = '';
                if (assignBtn) { assignBtn.disabled = true; assignBtn.textContent = 'Assign'; }
            }

            categorySelect.addEventListener('change', function() {
                const selectedCatId = Number(this.value);
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';
                let equipments = [];

                if (!selectedCatId) {
                    fullData.forEach(cat => { if (Array.isArray(cat.equipments)) equipments = equipments.concat(cat.equipments); });
                } else {
                    const category = fullData.find(c => c.id === selectedCatId);
                    if (!category) return;
                    equipments = category.equipments || [];
                }

                if (equipments.length === 0) {
                    const opt = document.createElement('option');
                    opt.textContent = 'No equipments'; opt.disabled = true; opt.selected = true;
                    equipmentSelect.appendChild(opt);
                    updateEquipmentStatus(); return;
                }

                const groups = { available: [], maintenance: [], damaged: [], rented: [], other: [] };
                equipments.forEach(eq => {
                    const s = (eq.current_status || '').toLowerCase();
                    if (s === 'available') groups.available.push(eq);
                    else if (s === 'maintenance') groups.maintenance.push(eq);
                    else if (s === 'damaged') groups.damaged.push(eq);
                    else if (s === 'rented') groups.rented.push(eq);
                    else groups.other.push(eq);
                });

                appendGroup('Available', groups.available);
                appendGroup('Maint. Hold', groups.maintenance);
                appendGroup('Damaged', groups.damaged);
                appendGroup('Rented', groups.rented);
                appendGroup('Other', groups.other);
                updateEquipmentStatus();
            });

            function updateEquipmentStatus() {
                const statusDiv  = document.getElementById('equipment-status-display');
                const pageLink   = document.getElementById('equipment-page-link');
                if (!equipmentSelect || !statusDiv || !assignBtn || !pageLink) return;

                const selectedOption = equipmentSelect.options[equipmentSelect.selectedIndex] || {};
                const status = selectedOption.getAttribute?.('data-current-status');
                const link   = selectedOption.getAttribute?.('data-link') || '';
                const title  = selectedOption.getAttribute?.('data-link-title') || '';

                if (!status) {
                    statusDiv.textContent = ''; statusDiv.className = 'text-sm font-semibold text-gray-600';
                    assignBtn.disabled = true; pageLink.href = ''; pageLink.textContent = '';
                    return;
                }

                const statusMap = {
                    available:   { text: 'Available',   color: 'text-green-600' },
                    rented:      { text: 'Rented',       color: 'text-gray-600' },
                    damaged:     { text: 'Not Available',color: 'text-red-600' },
                    maintenance: { text: 'Maint. Hold',  color: 'text-yellow-600' },
                };
                const info = statusMap[status] || { text: status, color: 'text-gray-600' };
                statusDiv.textContent  = `Status: ${info.text}`;
                statusDiv.className    = `text-sm font-semibold ${info.color}`;
                assignBtn.disabled     = false;
                pageLink.href          = link;
                pageLink.textContent   = title;
            }

            if (equipmentSelect) {
                equipmentSelect.addEventListener('change', updateEquipmentStatus);
                updateEquipmentStatus();
            }

            equipmentAssignForm?.addEventListener('submit', function(e) {
                e.preventDefault();
                if (window.$ && $(equipmentAssignForm).parsley && !$(equipmentAssignForm).parsley().isValid()) {
                    $(equipmentAssignForm).parsley().validate(); return;
                }
                const submitBtn = document.getElementById('equipment-assign-submit');
                if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Assigning...'; }

                const formData = new FormData(equipmentAssignForm);
                apiFetch('{{ route('admin.order-management.schedules.assign-equipment') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
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
                        fetchDispatch();
                    } else {
                        if (window.notyf) notyf.error(data?.message || 'Something went wrong.');
                    }
                })
                .finally(() => {
                    if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Assign'; }
                });
            });

            fetchEquipment();

            function fetchEquipment() {
                apiFetch('{{ route('admin.maintenance-management.equipment.fetch-with-categories') }}')
                    .then(data => {
                        if (data?.success) {
                            fullData = data.categories;
                            categorySelect.innerHTML = '<option value="">Select Category</option>';
                            data.categories.forEach(cat => {
                                const option = document.createElement('option');
                                option.value = cat.id; option.textContent = cat.title;
                                categorySelect.appendChild(option);
                            });
                            loadAllEquipments();
                            if (pendingPreferredCategoryId) applyPreferredCategory(pendingPreferredCategoryId);
                        }
                    });
            }

            function loadAllEquipments() {
                equipmentSelect.innerHTML = '<option value="">Select Equipment</option>';
                let equipments = [];
                fullData.forEach(cat => { if (Array.isArray(cat.equipments)) equipments = equipments.concat(cat.equipments); });
                const groups = { available: [], maintenance: [], damaged: [], rented: [], other: [] };
                equipments.forEach(eq => {
                    const s = (eq.current_status || '').toLowerCase();
                    if (s === 'available') groups.available.push(eq);
                    else if (s === 'maintenance') groups.maintenance.push(eq);
                    else if (s === 'damaged') groups.damaged.push(eq);
                    else if (s === 'rented') groups.rented.push(eq);
                    else groups.other.push(eq);
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
                list.forEach(eq => {
                    const opt = document.createElement('option');
                    opt.value = eq.unique_id;
                    opt.textContent = eq.equipment_name + ' || ' + eq.equipment_id;
                    opt.setAttribute('data-current-status', eq.current_status || '');
                    opt.setAttribute('data-link', eq.link || '');
                    opt.setAttribute('data-link-title', eq.link_title || '');
                    group.appendChild(opt);
                });
                equipmentSelect.appendChild(group);
            }
        });
    </script>

    {{-- ===== Driver Assignment Modal JS ===== --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const driverModal          = document.getElementById('driverAssignModal');
            const driverModalTitle     = document.getElementById('driver-modal-title');
            const driverModalSlotLabel = document.getElementById('driver-modal-slot-label');
            const driverModalOrder     = document.getElementById('driver-modal-order-number');
            const driverModalCustomer  = document.getElementById('driver-modal-customer');
            const driverModalProduct   = document.getElementById('driver-modal-product');
            const driverModalDate      = document.getElementById('driver-modal-date');
            const driverModalPhone     = document.getElementById('driver-modal-phone');
            const driverSelect         = document.getElementById('driver-select');
            const driverSlotInput      = document.getElementById('driver-modal-slot');
            const driverOPUidInput     = document.getElementById('driver-modal-order-product-uid');
            const driverOrderUidInput  = document.getElementById('driver-modal-order-uid');
            const driverSubmitBtn      = document.getElementById('driver-assign-submit');

            // Dispatch date section
            const rentalLabel           = document.getElementById('driver-modal-rental-label');
            const rentalDateDisplay     = document.getElementById('driver-modal-rental-date-display');
            const dispatchLabel         = document.getElementById('driver-modal-dispatch-label');
            const dispatchDateInput     = document.getElementById('driver-dispatch-date-input');
            const dispatchDateClear     = document.getElementById('driver-dispatch-date-clear');
            const dispatchDateBadge     = document.getElementById('driver-dispatch-date-badge');
            let   _dispatchDatePicker   = null;
            const alsoAssignCheck       = document.getElementById('driver-modal-also-assign');
            const alsoAssignLabel       = document.getElementById('driver-modal-also-assign-label');
            const otherDriverNote       = document.getElementById('driver-modal-other-driver-note');
            const deliveryLockedNote    = document.getElementById('driver-modal-delivery-locked-note');
            const otherSlotDriverIdIn   = document.getElementById('driver-modal-other-slot-driver-id');
            const otherSlotDriverNameIn = document.getElementById('driver-modal-other-slot-driver-name');
            const originalDispatchIn    = document.getElementById('driver-modal-original-dispatch-date');
            const rentalDeliveryRawIn   = document.getElementById('driver-modal-rental-delivery-date-raw');
            const rentalReturnRawIn     = document.getElementById('driver-modal-rental-return-date-raw');

            const driverPhones = @json($driverPhones ?? []);

            const updateScheduleUrlTemplate =
                '{{ route('admin.order-management.orders.update-product-schedule', [':order_uid', ':op_uid']) }}';
            const dispatchDateUrlTemplate =
                '{{ route("admin.order-management.dispatch.dispatch-date", [":op_uid"]) }}';

            // ---- Date display helper ----
            function fmtDate(iso) {
                if (!iso) return '-';
                const [y, m, d] = iso.split('-');
                const months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                return `${months[parseInt(m,10)-1]} ${parseInt(d,10)}, ${y}`;
            }

            // ---- Live dispatch date badge ----
            function updateDispatchBadge() {
                const slot      = driverSlotInput.value;
                const dateVal   = dispatchDateInput.value;
                const rentalDel = rentalDeliveryRawIn.value;
                const rentalRet = rentalReturnRawIn.value;

                dispatchDateBadge.className = 'hidden';
                dispatchDateClear.classList.toggle('hidden', !dateVal);

                if (!dateVal) return;

                if (slot === 'delivery' && rentalDel) {
                    if (dateVal < rentalDel) {
                        dispatchDateBadge.textContent = 'EARLY';
                        dispatchDateBadge.className   = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700';
                    } else if (dateVal > rentalDel) {
                        dispatchDateBadge.textContent = 'LATE';
                        dispatchDateBadge.className   = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-yellow-100 text-yellow-700';
                    }
                } else if (slot === 'return' && rentalRet) {
                    if (dateVal > rentalRet) {
                        dispatchDateBadge.textContent = 'LATE PICKUP';
                        dispatchDateBadge.className   = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700';
                    } else if (dateVal < rentalRet) {
                        dispatchDateBadge.textContent = 'EARLY';
                        dispatchDateBadge.className   = 'text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700';
                    }
                }
            }

            // ---- Flatpickr for dispatch date ----
            function initDispatchDatePicker() {
                if (_dispatchDatePicker) return;
                _dispatchDatePicker = flatpickr(dispatchDateInput, {
                    dateFormat:    'Y-m-d',
                    altInput:      true,
                    altFormat:     'M j, Y',
                    placeholder:   'Select Date',
                    altInputClass: 'border border-gray-300 rounded px-3 py-1.5 text-xs pr-8 w-40 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 cursor-pointer',
                    allowInput:    false,
                    disableMobile: true,
                    onReady: function (sel, str, instance) {
                        instance.calendarContainer.style.zIndex = '999999';
                    },
                    onChange: function () {
                        updateDispatchBadge();
                    },
                });
            }

            function loadFlatpickrThen(cb) {
                if (window.flatpickr) { cb(); return; }
                if (!document.getElementById('flatpickr-css')) {
                    var link = document.createElement('link');
                    link.id = 'flatpickr-css'; link.rel = 'stylesheet';
                    link.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
                    document.head.appendChild(link);
                }
                var s = document.createElement('script');
                s.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
                s.onload = cb;
                document.head.appendChild(s);
            }

            // Load + init on first modal open (deferred)
            loadFlatpickrThen(initDispatchDatePicker);

            dispatchDateClear?.addEventListener('click', function () {
                if (_dispatchDatePicker) _dispatchDatePicker.clear();
                else dispatchDateInput.value = '';
                updateDispatchBadge();
            });

            // ---- Open modal ----
            document.addEventListener('click', function (e) {
                const btn = e.target.closest('.assign-driver-btn');
                if (!btn) return;

                const slot               = btn.dataset.slot;
                const opUid             = btn.dataset.orderProductUniqueId;
                const orderUid          = btn.dataset.orderUniqueId;
                const orderNum          = btn.dataset.orderNumber;
                const customerName      = btn.dataset.customerName;
                const productName       = btn.dataset.productName;
                const date              = btn.dataset.deliveryDate;
                const currentDriver     = btn.dataset.currentDriverId;
                const rentalDelivery    = btn.dataset.rentalDeliveryDate    || '';
                const rentalReturn      = btn.dataset.rentalReturnDate      || '';
                const dispatchDelivery  = btn.dataset.dispatchDeliveryDate  || '';
                const dispatchReturn    = btn.dataset.dispatchReturnDate    || '';
                const otherDriverId     = btn.dataset.otherSlotDriverId     || '';
                const otherDriverName   = btn.dataset.otherSlotDriverName   || '';

                const isDelivery = slot === 'delivery';

                // Existing summary fields
                driverModalTitle.textContent     = isDelivery ? 'Assign Delivery Driver' : 'Assign Return Driver';
                driverModalSlotLabel.textContent = isDelivery ? '🚛 Delivery' : '↩ Return';
                driverModalSlotLabel.className   = isDelivery
                    ? 'inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-blue-100 text-blue-700'
                    : 'inline-flex items-center gap-1 text-xs font-semibold px-2 py-0.5 rounded-full bg-purple-100 text-purple-700';
                driverModalOrder.textContent    = orderNum    || '-';
                driverModalCustomer.textContent = customerName || '-';
                driverModalProduct.textContent  = productName  || '-';
                driverModalDate.textContent     = date         || '-';
                driverSelect.value              = currentDriver || '';
                updateDriverPhone(currentDriver);

                // Hidden context
                driverSlotInput.value      = slot;
                driverOPUidInput.value     = opUid;
                driverOrderUidInput.value  = orderUid;
                rentalDeliveryRawIn.value  = rentalDelivery;
                rentalReturnRawIn.value    = rentalReturn;
                otherSlotDriverIdIn.value  = otherDriverId;
                otherSlotDriverNameIn.value = otherDriverName;

                // Dispatch date section
                const dispatchVal = isDelivery ? dispatchDelivery : dispatchReturn;
                if (isDelivery) {
                    rentalLabel.textContent       = 'Rental Start:';
                    dispatchLabel.textContent     = 'Dispatch Delivery:';
                    rentalDateDisplay.textContent = rentalDelivery ? fmtDate(rentalDelivery) : '-';
                    alsoAssignLabel.textContent   = 'Also assign same Driver / Tech to return/pickup';
                } else {
                    rentalLabel.textContent       = 'Rental Return:';
                    dispatchLabel.textContent     = 'Dispatch Return:';
                    rentalDateDisplay.textContent = rentalReturn ? fmtDate(rentalReturn) : '-';
                    alsoAssignLabel.textContent   = 'Also assign same Driver / Tech to delivery';
                }
                if (_dispatchDatePicker) {
                    _dispatchDatePicker.setDate(dispatchVal || null, false);
                } else {
                    dispatchDateInput.value = dispatchVal;
                }
                originalDispatchIn.value = dispatchVal;
                updateDispatchBadge();

                // Other driver note
                if (otherDriverName) {
                    otherDriverNote.textContent = `Currently assigned: ${otherDriverName}`;
                    otherDriverNote.classList.remove('hidden');
                } else {
                    otherDriverNote.classList.add('hidden');
                }
                alsoAssignCheck.checked = true;

                // Delivery-completed protection: lock out "also assign to delivery" when delivery is already done
                const deliveryCompleted = btn.dataset.deliveryCompleted === '1';
                if (!isDelivery && deliveryCompleted) {
                    alsoAssignCheck.checked  = false;
                    alsoAssignCheck.disabled = true;
                    alsoAssignCheck.closest('label')?.classList.add('opacity-50', 'cursor-not-allowed');
                    deliveryLockedNote?.classList.remove('hidden');
                } else {
                    alsoAssignCheck.disabled = false;
                    alsoAssignCheck.closest('label')?.classList.remove('opacity-50', 'cursor-not-allowed');
                    deliveryLockedNote?.classList.add('hidden');
                }

                driverModal.classList.remove('hidden');
            });

            // ---- Phone helper ----
            function updateDriverPhone(driverId) {
                if (!driverModalPhone) return;
                const phone = driverId ? (driverPhones[driverId] || '') : '';
                if (phone) {
                    driverModalPhone.textContent = phone;
                    driverModalPhone.href        = 'tel:' + phone.replace(/\D/g, '');
                } else {
                    driverModalPhone.textContent = driverId ? 'N/A' : '-';
                    driverModalPhone.removeAttribute('href');
                }
            }
            driverSelect?.addEventListener('change', function () { updateDriverPhone(this.value); });

            // ---- Close modal ----
            document.getElementById('close-driver-modal')?.addEventListener('click',        closeDriverModal);
            document.getElementById('close-driver-modal-footer')?.addEventListener('click', closeDriverModal);
            driverModal?.addEventListener('click', function (e) {
                if (e.target === driverModal) closeDriverModal();
            });
            function closeDriverModal() {
                driverModal.classList.add('hidden');
                driverSelect.value = '';
                if (_dispatchDatePicker) { _dispatchDatePicker.clear(); _dispatchDatePicker.close(); }
                else dispatchDateInput.value = '';
                dispatchDateBadge.className = 'hidden';
                dispatchDateClear.classList.add('hidden');
                otherDriverNote.classList.add('hidden');
                alsoAssignCheck.checked  = true;
                alsoAssignCheck.disabled = false;
                alsoAssignCheck.closest('label')?.classList.remove('opacity-50', 'cursor-not-allowed');
                deliveryLockedNote?.classList.add('hidden');
                updateDriverPhone('');
            }

            // ---- API helpers ----
            const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            function apiSaveDriver(orderUid, opUid, slot, driverId) {
                const isDelivery = slot === 'delivery';
                const url = updateScheduleUrlTemplate
                    .replace(':order_uid', orderUid)
                    .replace(':op_uid',    opUid);
                return apiFetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({
                        _method: 'PUT',
                        type: isDelivery ? 'delivery' : 'return',
                        [isDelivery ? 'delivery_by' : 'pickup_by']: driverId === '' ? null : parseInt(driverId, 10),
                    }),
                });
            }

            function apiSaveDispatchDate(opUid, type, date) {
                const url = dispatchDateUrlTemplate.replace(':op_uid', opUid);
                return apiFetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
                    body: JSON.stringify({ type, date: date || null }),
                });
            }

            // ---- Save ----
            driverSubmitBtn?.addEventListener('click', async function () {
                const slot          = driverSlotInput.value;
                const opUid         = driverOPUidInput.value;
                const orderUid      = driverOrderUidInput.value;
                const driverId      = driverSelect.value;
                const dispatchDate  = dispatchDateInput.value;
                const originalDate  = originalDispatchIn.value;
                const alsoAssign    = alsoAssignCheck.checked;
                const otherDriverId = otherSlotDriverIdIn.value;
                const otherDriverName = otherSlotDriverNameIn.value;
                const isDelivery    = slot === 'delivery';
                const otherSlot     = isDelivery ? 'return' : 'delivery';

                if (!opUid || !orderUid) {
                    if (window.notyf) notyf.error('Missing order context. Please try again.');
                    return;
                }

                // Confirm before overwriting a different driver on the other slot
                if (alsoAssign && driverId && otherDriverId && otherDriverId !== driverId) {
                    const selectedName = driverSelect.options[driverSelect.selectedIndex]?.text || 'this driver';
                    const confirmed = confirm(
                        `The ${otherSlot} is already assigned to ${otherDriverName}. Replace with ${selectedName}?`
                    );
                    if (!confirmed) return;
                }

                driverSubmitBtn.disabled    = true;
                driverSubmitBtn.textContent = 'Saving…';

                try {
                    // 1. Save dispatch date override if changed
                    if (dispatchDate !== originalDate) {
                        const dateType = isDelivery ? 'delivery' : 'return';
                        const res = await apiSaveDispatchDate(opUid, dateType, dispatchDate);
                        if (!res?.success) {
                            if (window.notyf) notyf.error(res?.message || 'Failed to save dispatch date.');
                            return;
                        }
                    }

                    // 2. Save primary driver assignment
                    const res2 = await apiSaveDriver(orderUid, opUid, slot, driverId);
                    if (!res2?.success) {
                        if (window.notyf) notyf.error(res2?.message || 'Failed to update driver.');
                        return;
                    }

                    // 3. Also assign same driver to other slot (delivery only when driverId is set)
                    if (alsoAssign && driverId) {
                        const res3 = await apiSaveDriver(orderUid, opUid, otherSlot, driverId);
                        if (!res3?.success) {
                            if (window.notyf) notyf.error(res3?.message || `Failed to assign ${otherSlot} driver.`);
                            // Primary already saved — close and refresh anyway
                        }
                    }

                    closeDriverModal();
                    if (window.notyf) notyf.success('Driver updated.');
                    if (typeof window.fetchDispatch   === 'function') window.fetchDispatch();
                    if (typeof window.refreshDriverCards === 'function') window.refreshDriverCards();

                } catch (err) {
                    if (window.notyf) notyf.error('Something went wrong. Please try again.');
                } finally {
                    driverSubmitBtn.disabled    = false;
                    driverSubmitBtn.textContent = 'Save Driver';
                }
            });
        });
    </script>

    {{-- ===== Inline Priority Editor ===== --}}
    <script>
    (function () {
        const PRIORITY_URL_TEMPLATE = '{{ route("admin.order-management.dispatch.priority", ":uid") }}';

        function savePriorityToServer(uid, type, priority) {
            return fetch(PRIORITY_URL_TEMPLATE.replace(':uid', uid), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ type, priority }),
            }).then(r => r.json());
        }

        function resortJobColumn(savedBadge) {
            const jobEntry = savedBadge.parentElement;
            if (!jobEntry) return;
            const column = jobEntry.parentElement;
            if (!column) return;

            const entries = Array.from(column.children).filter(el => el.tagName === 'DIV');
            if (entries.length < 2) return;

            entries.sort((a, b) => {
                const ba = a.querySelector('.dispatch-priority-badge');
                const bb = b.querySelector('.dispatch-priority-badge');
                const pa = ba && ba.dataset.priority ? parseInt(ba.dataset.priority) : 9999;
                const pb = bb && bb.dataset.priority ? parseInt(bb.dataset.priority) : 9999;
                return pa - pb;
            });

            entries.forEach(el => column.appendChild(el));
        }

        function applyBadgeValue(badge, p, isBlue) {
            badge.dataset.priority = p ?? '';
            badge.textContent      = p ?? '—';
            if (p) {
                badge.className = badge.className
                    .replace('bg-gray-100 text-gray-400', isBlue ? 'bg-blue-100 text-blue-700' : 'bg-purple-100 text-purple-700');
            } else {
                badge.className = badge.className
                    .replace('bg-blue-100 text-blue-700',   'bg-gray-100 text-gray-400')
                    .replace('bg-purple-100 text-purple-700', 'bg-gray-100 text-gray-400');
            }
        }

        // Replace a priority badge with a tiny input, save on blur/Enter
        document.addEventListener('click', function (e) {
            const badge = e.target.closest('.dispatch-priority-badge');
            if (!badge || badge.querySelector('input')) return;

            const uid      = badge.dataset.uid;
            const type     = badge.dataset.type;
            const current  = badge.dataset.priority;
            const isBlue   = type === 'delivery';

            const input = document.createElement('input');
            input.type        = 'number';
            input.min         = '1';
            input.max         = '9999';
            input.value       = current;
            input.placeholder = '#';
            input.className   = 'w-7 h-7 text-center text-xs border rounded-full p-0 focus:outline-none ' +
                (isBlue ? 'border-blue-400 text-blue-700' : 'border-purple-400 text-purple-700');
            input.style.cssText = 'width:28px;height:28px;border-radius:50%;padding:0;text-align:center;font-size:11px;';

            badge.innerHTML = '';
            badge.appendChild(input);
            input.focus();
            input.select();

            // × button: lets dispatchers remove priority back to null without having to clear the field manually
            let clearRef = null;
            if (badge.parentElement) {
                const clearBtn = document.createElement('button');
                clearBtn.type = 'button';
                clearBtn.textContent = '×';
                clearBtn.title = 'Remove priority';
                clearBtn.style.cssText = 'font-size:14px;line-height:1;color:#9ca3af;font-weight:bold;cursor:pointer;margin-top:7px;padding:0 3px;';
                badge.parentElement.insertBefore(clearBtn, badge.nextSibling);
                clearRef = clearBtn;
                clearBtn.addEventListener('mousedown', function(ev) {
                    ev.preventDefault();
                    input.value = '';
                    save();
                });
            }

            let saving = false;

            // Centralises badge restoration and × button cleanup for all save paths
            function done(p) {
                clearRef?.remove();
                clearRef = null;
                if (p !== undefined) { applyBadgeValue(badge, p, isBlue); resortJobColumn(badge); }
                else                 { badge.textContent = current || '—'; }
            }

            function save() {
                if (saving) return;
                saving = true;

                const val      = input.value.trim();
                const priority = val === '' ? null : parseInt(val, 10);

                // Clear priority — no collision possible
                if (priority === null) {
                    savePriorityToServer(uid, type, null)
                        .then(data => {
                            if (data.success) { done(null); }
                            else              { done(); }
                        })
                        .catch(() => { done(); });
                    return;
                }

                // Detect collision within the active card section (separate OR combined)
                const cardSection = badge.closest('.driver-card-separate, .driver-card-combined');
                const siblings = cardSection
                    ? Array.from(cardSection.querySelectorAll('.dispatch-priority-badge')).filter(b => b !== badge)
                    : [];
                const collision = siblings.some(b => b.dataset.priority !== '' && parseInt(b.dataset.priority) === priority);

                if (collision) {
                    // Show confirmation — must do synchronously before async kicks in
                    const proceed = confirm(
                        `There is already an assignment at position ${priority}.\n\nWould you like to place this here and shift all assignments at position ${priority} and after up by 1?`
                    );
                    if (!proceed) {
                        done();
                        saving = false;
                        return;
                    }

                    // Collect all badges at >= new priority (excluding self), sort descending
                    const toShift = siblings
                        .filter(b => b.dataset.priority !== '' && parseInt(b.dataset.priority) >= priority)
                        .sort((a, b) => parseInt(b.dataset.priority) - parseInt(a.dataset.priority));

                    // Cascade-shift each one up by 1 on the server, highest first to avoid transient collisions
                    const shiftChain = toShift.reduce((chain, b) => {
                        return chain.then(() => {
                            const newP = parseInt(b.dataset.priority) + 1;
                            return savePriorityToServer(b.dataset.uid, b.dataset.type, newP)
                                .then(data => {
                                    if (data.success) {
                                        b.dataset.priority = newP;
                                        b.textContent      = newP;
                                    }
                                });
                        });
                    }, Promise.resolve());

                    shiftChain.then(() => {
                        return savePriorityToServer(uid, type, priority);
                    }).then(data => {
                        if (data.success) { done(data.priority); }
                        else              { done(); }
                    }).catch(() => { done(); });

                } else {
                    savePriorityToServer(uid, type, priority)
                        .then(data => {
                            if (data.success) { done(data.priority); }
                            else              { done(); }
                        })
                        .catch(() => { done(); });
                }
            }

            input.addEventListener('blur', save);
            input.addEventListener('keydown', e => {
                if (e.key === 'Enter') { e.preventDefault(); save(); }
                if (e.key === 'Escape') { done(); saving = true; }
            });
        });
    })();

    // Refresh dispatch table when the shared store modal saves a location change
    document.addEventListener('equipmentStoreUpdated', function () {
        if (typeof window.fetchDispatch === 'function') window.fetchDispatch();
    });
    </script>
@endpush
