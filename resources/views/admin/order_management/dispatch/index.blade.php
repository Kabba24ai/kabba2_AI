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
        /* Combined-load card on the driver board. Plain classes (not Tailwind
           utilities) so styling survives without an asset rebuild. */
        .dc-load { border: 1px solid #c7d2fe; background: #eef2ff; border-radius: .6rem; padding: .4rem; }
        .dc-load-head { display: flex; align-items: center; gap: .4rem; padding: 0 .25rem .3rem; }
        .dc-load-chip { font-size: 9px; font-weight: 700; letter-spacing: .04em; color: #3730a3; background: #e0e7ff; border-radius: .3rem; padding: .1rem .35rem; white-space: nowrap; }
        .dc-load-sub { font-size: 10px; color: #818cf8; flex: 1 1 auto; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .dc-load-btn { font-size: 10px; font-weight: 500; color: #4f46e5; background: none; border: 0; cursor: pointer; padding: 0; }
        .dc-load-btn:hover { text-decoration: underline; }
        .dc-load-btn-danger { color: #6b7280; }
        .dc-load-btn-danger:hover { color: #dc2626; }
        .dc-load-body { display: flex; flex-direction: column; gap: .5rem; }
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
        @include('admin.order_management.dispatch.partials._driver_cards', ['driverCards' => $driverCards, 'mode' => $mode ?? \App\Enums\Dispatch\DispatchDateRangeMode::Today])
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

        // Card jobs are assigned by definition — switch the list scope to All
        // so the jumped-to order is actually visible
        if (typeof window.setDispatchListFilter === 'function') {
            window.setDispatchListFilter('all');
        }

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

                <!-- Driver Workload controls (moved here from the cards header) -->
                <div class="flex items-center gap-3 flex-wrap bg-white rounded-md px-4 py-2 border">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Driver Workload</span>

                    {{-- Separate / Combined toggle --}}
                    <div id="driver-card-view-toggle" class="flex rounded-lg border border-gray-300 overflow-hidden text-xs">
                        <button type="button" id="dcv-separate"
                            class="px-3 py-1.5 font-semibold bg-blue-600 text-white flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 3H5a2 2 0 00-2 2v4m6-6h10a2 2 0 012 2v4M9 3v18m0 0h10a2 2 0 002-2V9M9 21H5a2 2 0 01-2-2V9m0 0h18"/></svg>
                            Separate
                        </button>
                        <button type="button" id="dcv-combined"
                            class="px-3 py-1.5 font-semibold bg-white text-gray-600 hover:bg-gray-50 border-l border-gray-300 flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                            Combined
                        </button>
                    </div>

                    {{-- Date range scope (Driver Workload cards + main table): All / 3 Days / Today --}}
                    <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden text-xs">
                        <span class="px-2 py-1.5 text-gray-400 font-medium border-r border-gray-300 bg-gray-50">Show:</span>
                        <button type="button" id="daf-all"
                            class="px-3 py-1.5 font-semibold bg-white text-gray-600 hover:bg-gray-50">All</button>
                        <button type="button" id="daf-3days"
                            class="px-3 py-1.5 font-semibold border-l border-gray-300 bg-white text-gray-600 hover:bg-gray-50">3 Days</button>
                        <button type="button" id="daf-today"
                            class="px-3 py-1.5 font-semibold border-l border-gray-300 bg-blue-600 text-white">Today</button>
                    </div>

                    {{-- Dispatch list scope: All / Unassigned Only --}}
                    <div class="flex items-center rounded-lg border border-gray-300 overflow-hidden text-xs">
                        <span class="px-2 py-1.5 text-gray-400 font-medium border-r border-gray-300 bg-gray-50">List:</span>
                        <button type="button" id="dlf-all"
                            class="px-3 py-1.5 font-semibold bg-white text-gray-600 hover:bg-gray-50">All</button>
                        <button type="button" id="dlf-unassigned"
                            class="px-3 py-1.5 font-semibold border-l border-gray-300 bg-blue-600 text-white">Unassigned Only</button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="mx-auto py-6">
        {{-- Combine toolbar: appears when ≥1 dispatch row is selected --}}
        <div id="dispatch-combine-bar" class="hidden mb-3 flex flex-wrap items-center gap-3 rounded-lg border border-blue-200 bg-blue-50 px-4 py-2.5 shadow-sm">
            <span class="text-sm font-semibold text-blue-800"><span id="dispatch-combine-count">0</span> selected</span>
            <span class="text-xs text-blue-500 hidden sm:inline">Combine line items into a single dispatch — one driver, one truck.</span>
            <div class="ml-auto flex items-center gap-2">
                <button type="button" id="dispatch-combine-clear" class="text-xs text-gray-500 hover:underline">Clear</button>
                <button type="button" id="dispatch-combine-open" class="px-3 py-1.5 rounded-md text-sm font-medium bg-blue-600 text-white hover:bg-blue-700">Combine into load</button>
            </div>
        </div>

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

    {{-- Combine / Assign-load modal --}}
    <div id="dispatchLoadModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5">
            <div class="flex items-center justify-between mb-2">
                <h3 id="dispatchLoadModalTitle" class="text-lg font-semibold text-gray-900">Combine into one dispatch</h3>
                <button type="button" class="dispatch-load-modal-close text-2xl leading-none text-gray-400 hover:text-gray-600">&times;</button>
            </div>
            <p id="dispatchLoadModalSummary" class="text-sm text-gray-500 mb-4"></p>

            <div id="dispatchLoadLegRow" class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Combine which leg</label>
                <div class="inline-flex rounded-lg border border-gray-300 overflow-hidden text-sm">
                    <button type="button" data-leg="delivery" class="dispatch-load-leg px-3 py-1.5">Deliveries</button>
                    <button type="button" data-leg="return" class="dispatch-load-leg px-3 py-1.5 border-l border-gray-300">Returns</button>
                    <button type="button" data-leg="both" class="dispatch-load-leg px-3 py-1.5 border-l border-gray-300">Both</button>
                </div>
                <p id="dispatchLoadLegHint" class="text-xs text-gray-400 mt-1"></p>
            </div>

            <div class="mb-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Driver</label>
                <select id="dispatchLoadDriver" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm">
                    <option value="">Select a driver…</option>
                    @foreach ($driverEmployees as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <button type="button" class="dispatch-load-modal-close px-4 py-2 rounded-lg text-sm border border-gray-300 text-gray-700 hover:bg-gray-100">Cancel</button>
                <button type="button" id="dispatchLoadConfirm" class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white hover:bg-blue-700">Combine</button>
            </div>
        </div>
    </div>

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
                // Reset list scope to the default (Unassigned Only)
                window.setDispatchListFilter('unassigned');
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

            // Dispatch list scope: 'unassigned' (default) or 'all'
            let dispatchListFilter = localStorage.getItem('dispatch_list_filter') || 'unassigned';

            window.setDispatchListFilter = function (filter, refetch = false) {
                dispatchListFilter = filter === 'all' ? 'all' : 'unassigned';
                localStorage.setItem('dispatch_list_filter', dispatchListFilter);
                const btnAll = document.getElementById('dlf-all');
                const btnUn  = document.getElementById('dlf-unassigned');
                if (btnAll && btnUn) {
                    const isAll = dispatchListFilter === 'all';
                    btnAll.classList.toggle('bg-blue-600', isAll);
                    btnAll.classList.toggle('text-white',  isAll);
                    btnAll.classList.toggle('bg-white',   !isAll);
                    btnAll.classList.toggle('text-gray-600', !isAll);
                    btnUn.classList.toggle('bg-blue-600', !isAll);
                    btnUn.classList.toggle('text-white',  !isAll);
                    btnUn.classList.toggle('bg-white',     isAll);
                    btnUn.classList.toggle('text-gray-600', isAll);
                }
                if (refetch) fetchDispatch();
            };
            window.setDispatchListFilter(dispatchListFilter);

            fetchDispatch(pageParam, perPageParam);

            // Expose globally so driver modal JS can call it after saving a driver
            window.fetchDispatch = fetchDispatch;

            // Apply saved card view mode and date-range filter on page load
            const savedDaf = localStorage.getItem('driver_assign_filter') || 'today';
            if (savedDaf !== 'today') {
                // Server-rendered cards default to Today Only; re-fetch to match stored preference
                setTimeout(() => window.refreshDriverCards(), 50);
            } else {
                setTimeout(() => applyDriverCardMode(localStorage.getItem('driver_card_view') || 'separate'), 0);
            }

            // Per-driver "View All" expansion is a deliberate user choice — remember it
            // so an auto-refresh (e.g. after a drag reorder) restores it instead of
            // collapsing every card back down. Session-scoped (resets on a full reload).
            const expandedDrivers = new Set();

            function setCardExpanded(card, expanded) {
                const sections = card.querySelectorAll('.dc-scroll-section');
                const btn = card.querySelector('.dc-view-all-btn');
                if (expanded) {
                    sections.forEach(s => { s.style.maxHeight = ''; s.style.overflowY = ''; });
                    card.dataset.expanded = 'true';
                    if (btn) btn.textContent = 'Collapse';
                } else {
                    sections.forEach(s => { s.style.maxHeight = '13rem'; s.style.overflowY = 'auto'; });
                    card.dataset.expanded = 'false';
                    if (btn) btn.textContent = 'View All';
                }
            }

            function applyExpandedState() {
                document.querySelectorAll('[data-driver-card]').forEach(card => {
                    if (expandedDrivers.has(card.dataset.driverId)) setCardExpanded(card, true);
                });
            }

            // Refresh driver workload cards without reloading the page
            window.refreshDriverCards = function (targetMode) {
                const wrapper = document.getElementById('driver-cards-wrapper');
                if (!wrapper) return;
                wrapper.classList.add('opacity-50');
                const range = localStorage.getItem('driver_assign_filter') || 'today';
                const cardsUrl = "{{ route('admin.order-management.dispatch.driver-cards') }}" + '?range=' + encodeURIComponent(range);
                apiFetch(cardsUrl, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(data => {
                    if (data?.html) {
                        wrapper.innerHTML = data.html;
                        // Re-apply the current card view mode after the DOM is replaced
                        const mode = targetMode || localStorage.getItem('driver_card_view') || 'separate';
                        applyDriverCardMode(mode);
                        // Re-attach drag-and-drop to the freshly injected cards
                        if (typeof window.initDispatchDnD === 'function') window.initDispatchDnD();
                        // Restore the user's per-card View All choices after the DOM swap
                        applyExpandedState();
                    }
                })
                // Remove BOTH loading classes — persist() adds pointer-events-none to
                // this wrapper before a reorder, and if it isn't cleared here the whole
                // board stays click/drag-dead until a full page reload.
                .finally(() => wrapper.classList.remove('opacity-50', 'pointer-events-none'));
            };

            // Switch the date-range filter (All / 3 Days / Today) — shared by the
            // Driver Workload cards and the main table — and re-fetch both.
            function applyDriverAssignFilter(filter) {
                localStorage.setItem('driver_assign_filter', filter);
                updateDafButtons(filter);
                window.refreshDriverCards();
                fetchDispatch();
            }

            // The Show All/3 Days/Today buttons now live in the static filter bar, so
            // JS owns their active styling (previously server-rendered in the partial)
            function updateDafButtons(range) {
                const btnAll    = document.getElementById('daf-all');
                const btn3Days  = document.getElementById('daf-3days');
                const btnToday  = document.getElementById('daf-today');
                if (!btnAll || !btn3Days || !btnToday) return;

                const setActive = (btn, active) => {
                    btn.classList.toggle('bg-blue-600', active);
                    btn.classList.toggle('text-white',  active);
                    btn.classList.toggle('bg-white',   !active);
                    btn.classList.toggle('text-gray-600', !active);
                };

                setActive(btnAll,   range === 'all');
                setActive(btn3Days, range === '3_days');
                setActive(btnToday, range === 'today');
            }
            updateDafButtons(savedDaf);

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
                if (e.target.closest('#daf-3days')) {
                    applyDriverAssignFilter('3_days');
                    return;
                }
                if (e.target.closest('#daf-today')) {
                    applyDriverAssignFilter('today');
                    return;
                }
                if (e.target.closest('#dlf-all')) {
                    window.setDispatchListFilter('all', true);
                    return;
                }
                if (e.target.closest('#dlf-unassigned')) {
                    window.setDispatchListFilter('unassigned', true);
                    return;
                }

                // View All / Collapse for driver cards — remember the choice per driver
                // so it survives the card refresh after a drag reorder.
                const viewAllBtn = e.target.closest('.dc-view-all-btn');
                if (viewAllBtn) {
                    const card = viewAllBtn.closest('[data-driver-card]');
                    if (!card) return;
                    const expand = card.dataset.expanded !== 'true';
                    setCardExpanded(card, expand);
                    if (expand) expandedDrivers.add(card.dataset.driverId);
                    else        expandedDrivers.delete(card.dataset.driverId);
                    return;
                }
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
                // Date-range filter (All / 3 Days / Today) — shared with the Driver
                // Workload cards via the same localStorage key.
                params.append('range', localStorage.getItem('driver_assign_filter') || 'today');
                if (driverFilterInput && driverFilterInput.value) params.append('driver_id', driverFilterInput.value);
                if (dispatchListFilter === 'unassigned') params.append('unassigned_only', 1);
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

    {{-- ===== Drag-and-drop dispatch board ===== --}}
    <style>
        .dc-drag-handle { touch-action: none; line-height: 0; }
        .dc-drag-ghost  { opacity: .35; }
        .dc-drag-chosen { background: #eff6ff; border-radius: .5rem; }
        .dc-drop-active { outline: 2px dashed #93c5fd; outline-offset: 2px; border-radius: .5rem; }
        .dc-idle-drop.dc-drop-active { outline-color: #6ee7b7; background: #ecfdf5; }
    </style>
    <script>
    (function () {
        const REORDER_URL = '{{ route("admin.order-management.dispatch.reorder") }}';
        let sortables = [];

        function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content; }

        // Read a list section's entries as [{uid, leg}] in DOM order (unified order).
        function itemsFromSection(section) {
            return Array.from(section.querySelectorAll('.dispatch-priority-badge'))
                .map(b => ({ uid: b.dataset.uid, leg: b.dataset.type }));
        }
        // Read a single-leg column's entries as [uid] in DOM order.
        function legOrderFromSection(section) {
            return Array.from(section.querySelectorAll('.dispatch-priority-badge')).map(b => b.dataset.uid);
        }
        // The driver's current unified order lives in the (possibly hidden) Combined
        // section of the same card — Sortable never touches it during a Separate-view
        // drag, so it is the pre-move snapshot the server needs to reconcile against.
        function currentUnifiedForCard(sectionEl) {
            const card = sectionEl.closest('[data-driver-card]');
            if (!card) return [];
            const comb = card.querySelector('.driver-card-combined .dc-scroll-section');
            return comb ? itemsFromSection(comb) : [];
        }
        function driverIdOf(el) {
            const card = el.closest('[data-driver-card]');
            return card ? parseInt(card.dataset.driverId, 10) : null;
        }

        // Visible number = position in the list, per the unified-route-sequence design.
        function relabel(section) {
            let pos = 0;
            section.querySelectorAll('.dc-entry').forEach(entry => {
                const badge = entry.querySelector('.dispatch-priority-badge');
                if (!badge || badge.querySelector('input')) return; // don't clobber the inline editor
                pos += 1;
                badge.textContent = String(pos);
            });
        }
        function relabelAll() {
            document.querySelectorAll('.dc-scroll-section[data-leg]').forEach(relabel);
        }

        function destroyDnD() {
            sortables.forEach(s => { try { s.destroy(); } catch (e) { /* node already gone */ } });
            sortables = [];
        }

        function highlight(sourceLeg, on) {
            document.querySelectorAll('.dc-scroll-section[data-leg="' + sourceLeg + '"]').forEach(el => {
                el.classList.toggle('dc-drop-active', on);
            });
            document.querySelectorAll('.dc-idle-drop').forEach(el => el.classList.toggle('dc-drop-active', on));
        }

        // Server is authoritative: re-render cards + lower list from the DB (preserving
        // filters/view/scope). This shows the new order on success and restores the
        // original order on failure/decline — the board's snap-back.
        function refreshBoard() {
            if (typeof window.refreshDriverCards === 'function') window.refreshDriverCards();
            if (typeof window.fetchDispatch === 'function') window.fetchDispatch();
        }

        function postReorder(payload) {
            return fetch(REORDER_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            }).then(r => r.json().then(d => ({ ok: r.ok, d })));
        }

        function persist(payload) {
            const wrapper = document.getElementById('driver-cards-wrapper');
            if (wrapper) wrapper.classList.add('opacity-50', 'pointer-events-none');

            postReorder(payload)
                .then(({ ok, d }) => {
                    if (ok && d && d.success) {
                        window.notyf?.success?.('Dispatch order updated.');
                    } else {
                        window.notyf?.error?.((d && d.message) || 'Could not update the dispatch order.');
                    }
                })
                .catch(() => {
                    window.notyf?.error?.('A network error occurred. The dispatch order was not changed.');
                })
                .finally(refreshBoard);
        }

        // Moving a job to another driver (or an idle driver) auto-places it by date and
        // requires the dispatcher to explicitly approve that placement. We first ask the
        // server where the card would land (no write), confirm, then commit.
        function previewThenCommit(payload) {
            const wrapper = document.getElementById('driver-cards-wrapper');
            if (wrapper) wrapper.classList.add('opacity-50');

            postReorder(Object.assign({}, payload, { preview: true }))
                .then(({ ok, d }) => {
                    if (wrapper) wrapper.classList.remove('opacity-50');

                    if (!ok || !d || !d.success || !d.preview) {
                        window.notyf?.error?.((d && d.message) || 'Could not place this job.');
                        refreshBoard();
                        return;
                    }

                    const p = d.preview;
                    const legName  = p.leg === 'delivery' ? 'Deliveries' : 'Returns';
                    const legLabel = p.leg === 'delivery' ? 'Delivery' : 'Return';
                    const where = payload.view === 'combined'
                        ? `position ${p.unified_position} of ${p.unified_total} in the route`
                        : `position ${p.leg_position} of ${p.leg_total} in ${legName}`;
                    const msg = `Assign ${legLabel} ${p.order_number}`
                        + (p.customer ? ` — ${p.customer}` : '')
                        + ` (${p.date_label}) to ${p.driver_name}?\n\n`
                        + `By date it will be placed at ${where}.\n\nApprove this placement?`;

                    if (window.confirm(msg)) {
                        persist(Object.assign({}, payload, { preview: false, confirmed: true }));
                    } else {
                        refreshBoard(); // declined → nothing was written; snap back
                    }
                })
                .catch(() => {
                    if (wrapper) wrapper.classList.remove('opacity-50');
                    window.notyf?.error?.('A network error occurred. The dispatch order was not changed.');
                    refreshBoard();
                });
        }

        function onDrop(evt) {
            highlight(evt.from.dataset.leg, false);

            const badge = evt.item.querySelector('.dispatch-priority-badge');
            if (!badge) return;

            const movedUid = badge.dataset.uid;
            const movedLeg = badge.dataset.type;

            const fromSection = evt.from;
            const toSection   = evt.to;
            const idleEl      = toSection.closest('[data-idle-driver-id]');
            const destIdle    = !!idleEl;

            const view      = fromSection.dataset.leg === 'combined' ? 'combined' : 'separate';
            const srcDriver = driverIdOf(fromSection);
            const dstDriver = destIdle ? parseInt(idleEl.dataset.idleDriverId, 10) : driverIdOf(toSection);

            // No-op (dropped back where it started, or a type-mismatch snap-back).
            if (evt.from === evt.to && evt.oldIndex === evt.newIndex) { relabelAll(); return; }
            if (srcDriver === null || dstDriver === null || isNaN(dstDriver)) { relabelAll(); return; }

            const crossDriver = srcDriver !== dstDriver;

            const payload = {
                view: view,
                leg: movedLeg,
                moved_uid: movedUid,
                source_driver_id: srcDriver,
                dest_driver_id: dstDriver,
                dest_idle: destIdle,
            };

            if (view === 'combined') {
                payload.dest_order = destIdle ? [{ uid: movedUid, leg: movedLeg }] : itemsFromSection(toSection);
                if (crossDriver) payload.source_order = itemsFromSection(fromSection);
            } else {
                if (!destIdle) {
                    payload.dest_leg_order = legOrderFromSection(toSection);
                    payload.dest_current_unified = currentUnifiedForCard(toSection);
                }
                if (crossDriver) {
                    payload.source_leg_order = legOrderFromSection(fromSection);
                    payload.source_current_unified = currentUnifiedForCard(fromSection);
                }
            }

            if (crossDriver || destIdle) {
                // Adding a job to another driver: auto-place by date + explicit approval.
                // The drop slot is not used; the server decides the date position. Leave
                // the relabel alone so we don't imply the drop slot is final.
                payload.placement = 'date';
                previewThenCommit(payload);
            } else {
                // Within-driver reorder: honour the exact dragged position immediately.
                payload.placement = 'manual';
                relabelAll(); // instant positional feedback before the authoritative refresh
                persist(payload);
            }
        }

        function groupFor(leg) {
            if (leg === 'combined') return { name: 'disp-combined', pull: true, put: ['disp-combined'] };
            if (leg === 'delivery') return { name: 'disp-delivery', pull: true, put: ['disp-delivery'] };
            return { name: 'disp-return', pull: true, put: ['disp-return'] };
        }

        window.initDispatchDnD = function () {
            // Graceful degradation: without SortableJS the inline editor + Update
            // button remain the working fallback.
            if (!window.Sortable) return;
            destroyDnD();

            document.querySelectorAll('.dc-scroll-section[data-leg]').forEach(section => {
                sortables.push(new Sortable(section, {
                    group: groupFor(section.dataset.leg),
                    handle: '.dc-drag-handle',
                    draggable: '.dc-entry',
                    animation: 150,
                    ghostClass: 'dc-drag-ghost',
                    chosenClass: 'dc-drag-chosen',
                    onStart: e => highlight(e.from.dataset.leg, true),
                    onEnd: onDrop,
                }));
            });

            // Idle drivers accept deliveries, returns, or combined cards — a first drop
            // assigns the job and creates its first priority position (handled server-side).
            document.querySelectorAll('.dc-idle-drop').forEach(zone => {
                sortables.push(new Sortable(zone, {
                    group: { name: 'disp-idle', pull: false, put: ['disp-delivery', 'disp-return', 'disp-combined'] },
                    draggable: '.dc-entry',
                    animation: 150,
                    // onEnd on the SOURCE list handles persistence; this instance only
                    // needs to accept the drop.
                }));
            });

            relabelAll();
        };

        document.addEventListener('DOMContentLoaded', function () {
            window.initDispatchDnD();
        });
    })();
    </script>

    {{-- ===== Combined dispatch loads: select + combine, and board load controls ===== --}}
    <script>
    (function () {
        const STORE_URL  = '{{ route("admin.order-management.dispatch.loads.store") }}';
        // Templates with a __ID__ placeholder for the load's unique_id.
        const ASSIGN_URL = '{{ route("admin.order-management.dispatch.loads.assign", ["load" => "__ID__"]) }}';
        const REMOVE_URL = '{{ route("admin.order-management.dispatch.loads.remove-member", ["load" => "__ID__"]) }}';
        const DESTROY_URL = '{{ route("admin.order-management.dispatch.loads.destroy", ["load" => "__ID__"]) }}';

        function csrf() { return document.querySelector('meta[name="csrf-token"]')?.content; }

        function post(url, method, body) {
            return fetch(url, {
                method: method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: body ? JSON.stringify(body) : null,
            }).then(r => r.json().then(d => ({ ok: r.ok, d })));
        }

        function refreshBoard() {
            if (typeof window.refreshDriverCards === 'function') window.refreshDriverCards();
            if (typeof window.fetchDispatch === 'function') window.fetchDispatch();
        }

        // ---- Lower-list selection + combine ----
        const bar = document.getElementById('dispatch-combine-bar');
        const countEl = document.getElementById('dispatch-combine-count');

        function selectedRows() {
            return Array.from(document.querySelectorAll('.dispatch-select-row:checked'));
        }

        function updateBar() {
            const n = selectedRows().length;
            if (countEl) countEl.textContent = String(n);
            if (bar) bar.classList.toggle('hidden', n === 0);
        }

        // Delegated (the table re-renders via AJAX).
        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('dispatch-select-all')) {
                const on = e.target.checked;
                document.querySelectorAll('.dispatch-select-row').forEach(cb => { cb.checked = on; });
                updateBar();
                return;
            }
            if (e.target.classList.contains('dispatch-select-row')) {
                updateBar();
            }
        });

        document.getElementById('dispatch-combine-clear')?.addEventListener('click', function () {
            document.querySelectorAll('.dispatch-select-row, .dispatch-select-all').forEach(cb => { cb.checked = false; });
            updateBar();
        });

        // ---- Modal ----
        const modal      = document.getElementById('dispatchLoadModal');
        const modalTitle = document.getElementById('dispatchLoadModalTitle');
        const modalSum   = document.getElementById('dispatchLoadModalSummary');
        const legRow     = document.getElementById('dispatchLoadLegRow');
        const legHint    = document.getElementById('dispatchLoadLegHint');
        const driverSel  = document.getElementById('dispatchLoadDriver');
        const confirmBtn = document.getElementById('dispatchLoadConfirm');

        // mode: 'combine' (uses selected rows) or 'assign' (existing load)
        let mode = 'combine';
        let chosenLeg = 'delivery';
        let assignLoadId = null;

        function openModal() { modal?.classList.remove('hidden'); }
        function closeModal() { modal?.classList.add('hidden'); }
        document.querySelectorAll('.dispatch-load-modal-close').forEach(b => b.addEventListener('click', closeModal));
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });

        function paintLegButtons() {
            document.querySelectorAll('.dispatch-load-leg').forEach(btn => {
                const active = btn.dataset.leg === chosenLeg;
                btn.classList.toggle('bg-blue-600', active);
                btn.classList.toggle('text-white', active);
                btn.classList.toggle('bg-white', !active);
                btn.classList.toggle('text-gray-600', !active);
                const disabled = btn.disabled;
                btn.classList.toggle('opacity-40', disabled);
                btn.classList.toggle('cursor-not-allowed', disabled);
            });
        }

        // Default the driver to the one the selected rows already share. For 'both',
        // require the same shared driver across both legs, else leave blank.
        function commonDriverForLeg(rows, leg) {
            if (leg === 'both') {
                const d = commonDriverForLeg(rows, 'delivery');
                const r = commonDriverForLeg(rows, 'return');
                return d && d === r ? d : '';
            }
            const key = leg === 'delivery' ? 'deliveryDriver' : 'returnDriver';
            const ids = rows.map(cb => cb.dataset[key]).filter(Boolean);
            if (ids.length !== rows.length) return '';
            return ids.every(id => id === ids[0]) ? ids[0] : '';
        }

        function openCombine() {
            const rows = selectedRows();
            if (rows.length < 2) {
                window.notyf?.error?.('Select at least two items to combine.');
                return;
            }
            mode = 'combine';
            assignLoadId = null;

            const deliveryOk = rows.every(cb => cb.dataset.deliveryActive === '1');
            const returnOk   = rows.every(cb => cb.dataset.returnActive === '1');
            if (!deliveryOk && !returnOk) {
                window.notyf?.error?.('The selected items do not share a combinable delivery or return leg (must be pending truck legs).');
                return;
            }

            const bothOk = deliveryOk && returnOk;
            // Prefer Both when possible (usually the right call), else the one valid leg.
            chosenLeg = bothOk ? 'both' : (deliveryOk ? 'delivery' : 'return');
            document.querySelectorAll('.dispatch-load-leg').forEach(btn => {
                const leg = btn.dataset.leg;
                btn.disabled = leg === 'delivery' ? !deliveryOk : (leg === 'return' ? !returnOk : !bothOk);
            });
            legRow.classList.remove('hidden');
            legHint.textContent = bothOk
                ? 'These items can be combined as deliveries, returns, or both.'
                : (deliveryOk ? 'Only the delivery leg is combinable for this selection.' : 'Only the return leg is combinable for this selection.');

            modalTitle.textContent = 'Combine into one dispatch';
            modalSum.textContent = rows.length + ' items will travel together on one driver.';
            confirmBtn.textContent = 'Combine';
            driverSel.value = commonDriverForLeg(rows, chosenLeg);
            paintLegButtons();
            openModal();
        }

        document.getElementById('dispatch-combine-open')?.addEventListener('click', openCombine);

        document.querySelectorAll('.dispatch-load-leg').forEach(btn => btn.addEventListener('click', function () {
            if (btn.disabled) return;
            chosenLeg = btn.dataset.leg;
            if (mode === 'combine') driverSel.value = commonDriverForLeg(selectedRows(), chosenLeg);
            paintLegButtons();
        }));

        confirmBtn?.addEventListener('click', function () {
            const driverId = driverSel.value;
            if (!driverId) { window.notyf?.error?.('Choose a driver for the load.'); return; }
            confirmBtn.disabled = true;

            const done = (ok, d) => {
                confirmBtn.disabled = false;
                if (ok && d && d.success) {
                    window.notyf?.success?.(mode === 'assign' ? 'Load reassigned.' : 'Items combined into one dispatch.');
                    closeModal();
                    document.querySelectorAll('.dispatch-select-row, .dispatch-select-all').forEach(cb => { cb.checked = false; });
                    updateBar();
                    refreshBoard();
                } else {
                    window.notyf?.error?.((d && d.message) || 'Could not complete the action.');
                }
            };

            if (mode === 'assign') {
                post(ASSIGN_URL.replace('__ID__', assignLoadId), 'POST', { driver_id: driverId })
                    .then(({ ok, d }) => done(ok, d)).catch(() => done(false));
            } else {
                const uids = selectedRows().map(cb => cb.dataset.uid);
                post(STORE_URL, 'POST', { member_uids: uids, leg: chosenLeg, driver_id: driverId })
                    .then(({ ok, d }) => done(ok, d)).catch(() => done(false));
            }
        });

        // ---- Board load controls (delegated; the cards re-render via AJAX) ----
        document.addEventListener('click', function (e) {
            const assignBtn = e.target.closest('.dc-load-assign');
            if (assignBtn) {
                const load = assignBtn.closest('.dc-load');
                if (!load) return;
                mode = 'assign';
                assignLoadId = load.dataset.loadId;
                legRow.classList.add('hidden');
                modalTitle.textContent = 'Assign load to a driver';
                modalSum.textContent = 'The whole load moves to the chosen driver as one unit.';
                confirmBtn.textContent = 'Assign';
                driverSel.value = '';
                openModal();
                return;
            }

            const ungroupBtn = e.target.closest('.dc-load-ungroup');
            if (ungroupBtn) {
                const load = ungroupBtn.closest('.dc-load');
                if (!load) return;
                if (!window.confirm('Ungroup this load? The items stay on the driver but are no longer combined.')) return;
                post(DESTROY_URL.replace('__ID__', load.dataset.loadId), 'DELETE')
                    .then(({ ok, d }) => {
                        if (ok && d && d.success) { window.notyf?.success?.('Load ungrouped.'); }
                        else { window.notyf?.error?.((d && d.message) || 'Could not ungroup.'); }
                    })
                    .catch(() => window.notyf?.error?.('A network error occurred.'))
                    .finally(refreshBoard);
                return;
            }

            const removeBtn = e.target.closest('.dc-load-remove');
            if (removeBtn) {
                const load = removeBtn.closest('.dc-load');
                if (!load) return;
                post(REMOVE_URL.replace('__ID__', load.dataset.loadId), 'POST', { member_uid: removeBtn.dataset.uid })
                    .then(({ ok, d }) => {
                        if (ok && d && d.success) { window.notyf?.success?.('Removed from load.'); }
                        else { window.notyf?.error?.((d && d.message) || 'Could not remove item.'); }
                    })
                    .catch(() => window.notyf?.error?.('A network error occurred.'))
                    .finally(refreshBoard);
            }
        });
    })();
    </script>
@endpush
