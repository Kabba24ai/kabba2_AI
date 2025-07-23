@extends('admin.layouts.app')

@section('title', 'Schedules')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-calendar-days class="w-6 h-6 text-blue-600" />
            Schedule Management
        </h1>
        <a href="{{ route('admin.order-management.schedules.index') }}"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
            <x-heroicon-o-arrow-path class="w-5 h-5" />
            Reload
        </a>
    </div>

    <div class="bg-white p-4 rounded-md shadow-sm space-y-4">
        <!-- Row 1: Inputs & Selects -->
            <div class="flex flex-wrap gap-4 items-center">
                <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_name" placeholder="Customer name" name="customer_name"
                        value="{{ request('customer_name') }}"
                        class="pl-3 pr-10 py-2 h-11 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            <div class="w-full sm:w-48">
                <div class="relative bg-white">
                    <input type="text" id="customer_company_name" placeholder="Customer company" name="customer_company_name"
                        value="{{ request('customer_company_name') }}"
                        class="pl-3 pr-10 py-2 h-11 border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            {{-- Search Phone --}}
            <div>
                {{-- <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">Search Phone</label> --}}
                <div class="relative bg-white">
                    <input type="text" id="customer_phone" placeholder="(xxx) xxx-xxxx" name="customer_phone"
                    value="{{ request('customer_phone') }}"
                    class="masked-phone pl-3 pr-10 py-2 h-11 border border-gray-300 rounded-md text-sm w-38 focus:ring-blue-500 focus:border-blue-500" />
                    <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 right-3 top-1/2 transform -translate-y-1/2" />
                </div>
            </div>

            {{-- Category --}}
            <div class="w-full sm:w-48">
                {{-- <label for="category" class="block text-sm font-medium text-gray-700 mb-1">Category</label> --}}
                <select name="category"
                    class="choices-select w-full  rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">Select Category</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                            {{ $category->title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Payment Method --}}
            <div>
                {{-- <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-1">Payment Type</label> --}}
                <select id="payment_method" name="payment_method"
                    class="h-11 border bg-white border-gray-300 rounded-md px-3 py-2 text-sm w-38 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payment Types</option>
                    <option value="Card" @selected(request('payment_method') == 'Card')>Card</option>
                    <option value="COD" @selected(request('payment_method') == 'COD')>COD</option>
                    <option value="Account" @selected(request('payment_method') == 'Account')>Account</option>
                </select>
            </div>

            {{-- Payment Status --}}
            <div>
                {{-- <label for="payment_status" class="block text-sm font-medium text-gray-700 mb-1">Payment</label> --}}
                <select id="payment_status" name="payment_status"
                    class="h-11 border bg-white border-gray-300 rounded-md px-3 py-2 text-sm w-34 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">All Payments</option>
                    <option value="Completed" @selected(request('payment_status') == 'Completed')>Paid</option>
                    <option value="Pending" @selected(request('payment_status') == 'Pending')>Pending</option>
                    <option value="Refund" @selected(request('payment_status') == 'Refund')>Refund</option>
                    <option value="Refund" @selected(request('payment_status') == 'Refund')>Refund</option>
                    <option value="Failed" @selected(request('payment_status') == 'Failed')>Failed</option>
                </select>
            </div>

            <!-- Date Dropdown -->
            <div>
                {{-- <label for="date_filter" class="block text-sm font-medium text-gray-700 mb-1">Filter by Date</label> --}}
                <select name="date_filter" id="date_filter"
                    class="h-11 border border-gray-300 rounded-lg px-3 py-2 text-sm focus:border-blue-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                    <option value="">All Dates</option>
                    <option value="today" @selected(request('date_filter') == 'today')>Today</option>
                    <option value="week" @selected(request('date_filter') == 'week')>This Week</option>
                    <option value="month" @selected(request('date_filter') == 'month')>This Month</option>
                </select>
            </div>
        </div>
         @php
            // If param is not set at all, default both checked. If set (even empty array), use only what is present.
            $transportMode = request()->input('transport_mode');
            $scheduleType = request()->input('schedule_type');
            $storeLocation = request()->input('store_location');
        @endphp

        <!-- Row 2: Checkboxes & Date Filter -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex flex-wrap items-center gap-6">
                <!-- Schedule Type -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border">
                    <x-heroicon-o-calendar class="w-5 h-5 text-blue-500" />
                    <span class="font-medium">Schedule Type</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Delivery" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($scheduleType) ? in_array('Delivery', $scheduleType) : true)>
                        Deliver
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Return" name="schedule_type[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($scheduleType) ? in_array('Return', $scheduleType) : true)>
                        Return
                    </label>
                </div>

                <!-- Transport Mode -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border">
                    <x-heroicon-o-truck class="w-5 h-5 text-green-500" />
                    <span class="font-medium">Transport Mode</span>

                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="transport_mode[]" value="Truck"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($transportMode) ? in_array('Truck', $transportMode) : true)>
                        Truck
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="transport_mode[]" value="Store"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" @checked(is_array($transportMode) ? in_array('Store', $transportMode) : true)>
                        In Store
                    </label>
                </div>
                <!-- Store Locations -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border">
                    <x-heroicon-o-map-pin class="w-5 h-5 text-purple-500" />
                    <span class="font-medium">Store Locations</span>
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
                <!-- Special Filters -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border">
                    <x-heroicon-o-funnel class="w-5 h-5 text-orange-500" />
                    <span class="font-medium text-gray-700">Special Filters</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="rescheduled_only" value="Reschedule" id="rescheduled_only"
                            class="text-red-600 focus:ring-red-500 rounded border-gray-300" @checked(request('rescheduled_only') == 'Reschedule')>
                        <span class="font-medium">Rescheduled Only</span>
                    </label>
                </div>
            </div>
        </div>
    </div>

    <div class="mx-auto py-6">
        <div id="schedule-table-wrapper">
            @include('admin.order_management.schedules.partials._table', [
                'orderProducts' => $orderProducts,
            ])
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let customerNameInput = document.querySelector('input[name="customer_name"]');
            let customerCompanyNameInput = document.querySelector('input[name="customer_company_name"]');
            let customerPhoneInput = document.querySelector('input[name="customer_phone"]');
            let categoryInput = document.querySelector('select[name="category"]');
            let paymentStatusInput = document.querySelector('select[name="payment_status"]');
            let paymentMethodInput = document.querySelector('select[name="payment_method"]');
            let dateFilterInput = document.querySelector('select[name="date_filter"]');
            let storeLocationInputs = document.querySelectorAll('input[name="store_location[]"]');
            let transportModeInputs = document.querySelectorAll('input[name="transport_mode[]"]');
            let scheduleTypeInputs = document.querySelectorAll('input[name="schedule_type[]"]');
            let rescheduledOnlyInput = document.querySelector('input[name="rescheduled_only"]');

            let loadingIndicator = document.querySelector('#schedule-loading');
            let wrapper = document.querySelector('#schedule-table-wrapper');
            let timeout = null;

            function fetchSchedules() {
                const params = new URLSearchParams();
                params.set('page', 1);
                if (customerNameInput && (customerNameInput.value.length >= 3 || customerNameInput.value.length ===
                        0)) params.append('customer_name', customerNameInput.value);
                if (customerCompanyNameInput && (customerCompanyNameInput.value.length >= 3 || customerCompanyNameInput.value
                        .length === 0)) params.append('customer_company_name', customerCompanyNameInput.value);
                if (customerPhoneInput && (customerPhoneInput.value.length >= 3 || customerPhoneInput.value
                        .length === 0)) params.append('customer_phone', customerPhoneInput.value);
                if (categoryInput && categoryInput.value) params.append('category', categoryInput.value);
                if (paymentStatusInput && paymentStatusInput.value) params.append('payment_status',
                    paymentStatusInput.value);
                if (dateFilterInput && dateFilterInput.value) params.append('date_filter', dateFilterInput.value);
                if (rescheduledOnlyInput && rescheduledOnlyInput.checked) params.append('rescheduled_only',
                rescheduledOnlyInput.value);

                // --- START: Handle schedule_type[] checkboxes ---
                let anyScheduleTypeChecked = false;

                scheduleTypeInputs.forEach(input => {
                    if (input.checked) {
                        params.append('schedule_type[]', input.value);
                        anyScheduleTypeChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyScheduleTypeChecked) {
                    params.append('schedule_type[]', false);
                }
                // --- END: Handle schedule_type[] checkboxes ---

                // --- START: Handle transport_mode[] checkboxes ---
                let anyTransportModeChecked = false;
                transportModeInputs.forEach(input => {
                    if (input.checked) {
                        params.append('transport_mode[]', input.value);
                        anyTransportModeChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyTransportModeChecked) {
                    params.append('transport_mode[]', false);
                }
                // --- END: Handle transport_mode[] checkboxes ---

                // --- START: Handle store_location[] checkboxes ---
                let anyStoreLocationChecked = false;
                storeLocationInputs.forEach(input => {
                    if (input.checked) {
                        params.append('store_location[]', input.value);
                        anyStoreLocationChecked = true;
                    }
                });
                // If none are checked, send an empty value so backend knows it's user intent
                if (!anyStoreLocationChecked) {
                    params.append('store_location[]', false);
                }
                // --- END: Handle store_location[] checkboxes ---

                // Show loader
                loadingIndicator.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.order-management.schedules.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                    })
                    .finally(() => {
                        loadingIndicator.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            if (customerNameInput) customerNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (customerCompanyNameInput) customerCompanyNameInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (customerPhoneInput) customerPhoneInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchSchedules, 400);
            });
            if (categoryInput) categoryInput.addEventListener('change', fetchSchedules);
            if (paymentStatusInput) paymentStatusInput.addEventListener('change', fetchSchedules);
            if (dateFilterInput) dateFilterInput.addEventListener('change', fetchSchedules);
            storeLocationInputs.forEach(input => input.addEventListener('change', fetchSchedules));
            if (rescheduledOnlyInput) rescheduledOnlyInput.addEventListener('change', fetchSchedules);

            transportModeInputs.forEach(input => {
                input.addEventListener('change', function (e) {
                    let checked = Array.from(transportModeInputs).filter(i => i.checked);
                    if (checked.length === 0) {
                        e.preventDefault();
                        input.checked = true;
                    } else {
                        fetchSchedules();
                    }
                });
            });

            scheduleTypeInputs.forEach(input => {
                input.addEventListener('change', function (e) {
                    // Collect all checked checkboxes
                    let checked = Array.from(scheduleTypeInputs).filter(i => i.checked);
                    // If this action would uncheck the last one, prevent it
                    if (checked.length === 0) {
                        // Prevent unchecking the last one
                        e.preventDefault();
                        input.checked = true;
                    } else {
                        fetchSchedules();
                    }
                });
            });
        });
    </script>
@endpush
