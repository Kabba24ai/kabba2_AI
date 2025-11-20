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
                    <input type="text" id="customer_company_name" placeholder="Customer company"
                        name="customer_company_name" value="{{ request('customer_company_name') }}"
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
                    @foreach ($categories as $id => $category)
                        <option value="{{ $id }}" @selected(request('category') == $id)>
                            {{ $category }}
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
                    <option value="Paid" @selected(request('payment_status') == 'Paid')>Paid</option>
                    <option value="Pending" @selected(request('payment_status') == 'Pending')>Pending</option>
                    <option value="Account" @selected(request('payment_status') == 'Account')>Account</option>
                    <option value="Partial Refund" @selected(request('payment_status') == 'Partial Refund')>Partial Refund</option>
                    <option value="Refunded" @selected(request('payment_status') == 'Refunded')>Refunded</option>
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

    <!-- Equipment Assign Modal -->
    <div id="equipmentAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="addressModalTitle" class="text-lg font-semibold">Assign Equipment : <span
                        id="equipmentAssignModalTitle"></span></h2>
                <button type="button"
                    class="close-equipment-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'equipmentAssignForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="user_unique_id">User</label>
                    {!! html()->select('user_unique_id', $employees)->id('user_unique_id')->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                        ])->required() !!}
                </div>

                <div>
    <label class="text-sm font-medium text-gray-700 required">Category</label>
    <select id="category_select"
        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm bg-white text-gray-700">
        <option value="">Select Category</option>
    </select>
</div>


                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="equipment_unique_id">Equipment</label>
                    <select name="equipment_unique_id" id="equipment_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700">
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
                    class="close-equipment-assign-modal px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="equipment-assign-submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
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
            const perPage = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;

            function fetchSchedules() {
                const params = new URLSearchParams();
                params.set('page', 1);
                if (customerNameInput && (customerNameInput.value.length >= 3 || customerNameInput.value.length ===
                        0)) params.append('customer_name', customerNameInput.value);
                if (customerCompanyNameInput && (customerCompanyNameInput.value.length >= 3 ||
                        customerCompanyNameInput.value
                        .length === 0)) params.append('customer_company_name', customerCompanyNameInput.value);
                if (customerPhoneInput && (customerPhoneInput.value.length >= 3 || customerPhoneInput.value
                        .length === 0)) params.append('customer_phone', customerPhoneInput.value);
                if (categoryInput && categoryInput.value) params.append('category', categoryInput.value);
                if (paymentStatusInput && paymentStatusInput.value) params.append('payment_status',
                    paymentStatusInput.value);
                if (paymentMethodInput && paymentMethodInput.value) params.append('payment_method',
                    paymentMethodInput.value);
                if (dateFilterInput && dateFilterInput.value) params.append('date_filter', dateFilterInput.value);
                if (rescheduledOnlyInput && rescheduledOnlyInput.checked) params.append('rescheduled_only',
                    rescheduledOnlyInput.value);
                if (perPage) params.append('per_page', perPage);

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
            if (paymentMethodInput) paymentMethodInput.addEventListener('change', fetchSchedules);
            if (dateFilterInput) dateFilterInput.addEventListener('change', fetchSchedules);
            storeLocationInputs.forEach(input => input.addEventListener('change', fetchSchedules));
            if (rescheduledOnlyInput) rescheduledOnlyInput.addEventListener('change', fetchSchedules);

            transportModeInputs.forEach(input => {
                input.addEventListener('change', function(e) {
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
                input.addEventListener('change', function(e) {
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

            const modal = document.getElementById('equipmentAssignModal');
            const equipmentAssignModalTitle = document.getElementById('equipmentAssignModalTitle');
            const equipmentAssignForm = document.getElementById('equipmentAssignForm');
            const categorySelect = document.getElementById('category_select');
            const equipmentSelect = document.getElementById('equipment_unique_id');
            const assignBtn = document.getElementById('equipment-assign-submit');
            const statusDisplayId = 'equipment-status-display';
            const equipmentPageLinkId = 'equipment-page-link';

            let fullData = {}; // store categories + equipment


            // --- Event delegation for OPEN buttons (works after table refresh) ---
            document.addEventListener('click', function(e) {
                const btn = e.target.closest('.equipment-assign-btn');
                if (!btn) return;

                const orderProductUniqueId = btn.getAttribute('data-order-product-unique-id');
                const orderProductName = btn.getAttribute('data-order-product-name');
                const orderNumber = btn.getAttribute('data-order');

                document.getElementById('order-product-unique-id').value = orderProductUniqueId || '';
                equipmentAssignModalTitle.textContent = (orderNumber || '') + ' ' + (orderProductName ||
                '');
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
                if (statusDiv) {
                    statusDiv.textContent = '';
                    statusDiv.className = 'mt-2 text-sm font-semibold text-gray-600';
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

           categorySelect.addEventListener('change', function () {

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
    } 
    else {
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

    // Helper to create optgroup
    function appendGroup(label, list) {
        if (list.length === 0) return;

        const group = document.createElement('optgroup');
        group.label = label;

        list.forEach(equipment => {
            const opt = document.createElement('option');
            opt.value = equipment.unique_id;
            opt.textContent = equipment.equipment_name;
            opt.setAttribute('data-current-status', equipment.current_status || '');
            opt.setAttribute('data-link', equipment.link || '');
            opt.setAttribute('data-link-title', equipment.link_title || '');
            group.appendChild(opt);
        });

        equipmentSelect.appendChild(group);
    }

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
        isAvailable = false;

        // SweetAlert message for rented items
        window.showError(
            "This item is currently Rented, so it cannot be assigned to this Order.",
            "Rented "
        );

        break;


                    case 'damaged':
                        statusText = 'Not Available';
                        statusColor = 'text-red-600';
                        isAvailable = false;
                        break;
                    case 'maintenance':
                        statusText = 'Maint. Hold';
                        statusColor = 'text-yellow-600';
                        isAvailable = false;
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

            fetchEquipment();

//  Fetch equipment options with cat
           function fetchEquipment() {
    apiFetch('{{ route('admin.maintenance-management.equipment.fetch-with-categorys') }}')
        .then(data => {
            if (data?.success) {

                fullData = data.categories; // store full categories

                categorySelect.innerHTML = '<option value="">Select Category</option>';

                data.categories.forEach(cat => {
                    const option = document.createElement('option');
                    option.value = cat.id;
                    option.textContent = cat.title;
                    categorySelect.appendChild(option);
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

    function appendGroup(label, list) {
        if (list.length === 0) return;

        const group = document.createElement('optgroup');
        group.label = label;

        list.forEach(equipment => {
            const opt = document.createElement('option');
            opt.value = equipment.unique_id;
            opt.textContent = equipment.equipment_name;
            opt.setAttribute('data-current-status', equipment.current_status || '');
            opt.setAttribute('data-link', equipment.link || '');
            opt.setAttribute('data-link-title', equipment.link_title || '');
            group.appendChild(opt);
        });

        equipmentSelect.appendChild(group);
    }

    appendGroup('Available', groups.available);
    appendGroup('Maint. Hold', groups.maintenance);
appendGroup('Damaged', groups.damaged);
appendGroup('Rented', groups.rented);
appendGroup('Other', groups.other);


    updateEquipmentStatus();
}



        });
    </script>
@endpush
