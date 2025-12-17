@extends('admin.layouts.app')

@section('title', 'Schedule Assignment')

@push('css')
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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">

            {{-- Search Input --}}
            <div class="relative w-full sm:w-48">
                <input type="text" name="search" placeholder="Search equipment, ID, or customer"
                    value="{{ request('search') }}"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-4 pr-10 text-sm text-gray-900 shadow-sm " />
                <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
            </div>

            {{-- Select Category --}}
            <div class="w-full sm:w-48">
                <select name="category"
                    class=" w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                    <option value="">All Product Categories</option>
                    @foreach ($categories as $id => $title)
                        <option value="{{ $id }}" @selected(request('category') == $id)>
                            {{ $title }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Type Dropdown --}}
            <div class="w-full sm:w-48">
                <select name="store"
                    class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm">
                    <option value="">All Stores</option>
                    @foreach ($stores as $store)
                        <option value="{{ $store->id }}" @selected(request('store') == $store->id)>{{ $store->store_name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Equipment Status -->
            <div class=" whitespace-nowrap">
                <div
                    class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
                    <svg class=" w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="font-medium">Equipment Status</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Available" name="equipment_status[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            {{ request('equipment_status') === null || in_array('Available', (array) request('equipment_status')) ? 'checked' : '' }}>
                        Available
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Rented" name="equipment_status[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            {{ request('equipment_status') === null || in_array('Rented', (array) request('equipment_status')) ? 'checked' : '' }}>
                        Rented
                    </label>
                </div>
            </div>

            <div class=" whitespace-nowrap">
                <!-- Issues & Maintenance -->
                <div
                    class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                        class="lucide lucide-wrench w-5 h-5 text-orange-500">
                        <path
                            d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z">
                        </path>
                    </svg>
                    <span class="font-medium">Issues & Maintenance </span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" name="equipment_status[]" value="Maintenance"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            {{ request('equipment_status') === null || in_array('Maintenance', (array) request('equipment_status')) ? 'checked' : '' }}>
                        Maint. Hold
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="equipment_status[]" value="Damaged"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300"
                            {{ request('equipment_status') === null || in_array('Damaged', (array) request('equipment_status')) ? 'checked' : '' }}>
                        Damaged
                    </label>
                </div>
            </div>

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
        @if ($orderProducts->total() > 0)
            <div class="bg-white shadow-sm rounded-lg {{ ($orderProducts->total() > 4) ? 'flex-1' : '' }} flex flex-col min-h-0">
                <h2 class="text-lg font-semibold p-5">
                    Unassigned Orders ({{ $orderProducts->total() }})
                </h2>

                <div id="schedule-table-wrapper"
                    class="overflow-x-auto overflow-y-auto flex-1 min-h-0">
                    @include('admin.order_management.schedule_assignment.partials._table2', [
                        'orderProducts' => $orderProducts,
                    ])
                </div>
            </div>
        @endif
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
        document.querySelectorAll(".tooltip-trigger").forEach(el => {
            const tooltip = document.getElementById("tooltip-global");

            el.addEventListener("mouseenter", () => {
                tooltip.innerHTML = el.dataset.tooltip;
                tooltip.classList.remove("hidden");

                const rect = el.getBoundingClientRect();
                const tRect = tooltip.getBoundingClientRect();

                tooltip.style.left = (rect.left + rect.width / 2 - tRect.width / 2) + "px";
                tooltip.style.top = (rect.top - tRect.height - 8) + "px";
            });

            el.addEventListener("mouseleave", () => {
                tooltip.classList.add("hidden");
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.querySelector('input[name="search"]');
            let categorySelect = document.querySelector('select[name="category"]');
            // let statusSelect = document.querySelector('select[name="status"]');
            let storeSelect = document.querySelector('select[name="store"]');
            let equipmentTableWrapper = document.querySelector('#equipment-table-wrapper');
            let equipmentStatusCheckboxes = document.querySelectorAll('input[name="equipment_status[]"]');
            let timeout = null;
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;


            const screenKey = "equipment_filters";

            const fieldMap = {
                'search': searchInput,
                'category': categorySelect,
                // 'status': statusSelect,
                'store': storeSelect,
                'equipment_status': equipmentStatusCheckboxes,
            };

            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            fetchEquipments(pageParam, perPageParam); // initial fetch after loading saved filters
            function fetchEquipments(page = 1, perPage = 10) {
                const params = new URLSearchParams();
                if (searchInput.value.length >= 3 || searchInput.value === '') params.append('search', searchInput
                    .value);
                if (categorySelect.value) params.append('category', categorySelect.value);
                // if (statusSelect.value) params.append('status', statusSelect.value);
                if (storeSelect.value) params.append('store', storeSelect.value);
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
                    })
                    .catch(err => {
                        equipmentTableWrapper.innerHTML = '<div class="text-red-500 p-4">Error loading equipment.</div>';
                        console.error(err);
                    })
                    .finally(() => {
                        // loader.classList.add('hidden');
                        equipmentTableWrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

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
            [categorySelect, storeSelect].forEach(el => el.addEventListener('change', fetchEquipments));

            // Immediate filter for checkboxes
            equipmentStatusCheckboxes.forEach(cb => cb.addEventListener('change', fetchEquipments));


            let loadingIndicator = document.querySelector('#schedule-loading');
            let wrapper = document.querySelector('#schedule-table-wrapper');

            function fetchSchedules() {
                const params = new URLSearchParams();
                params.append('unassigned_equipment', 1);
                params.append('per_page', 'all');

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


            const modal = document.getElementById('equipmentAssignModal');
            const equipmentAssignModalTitle = document.getElementById('equipmentAssignModalTitle');
            const equipmentAssignForm = document.getElementById('equipmentAssignForm');
            const equipmentCategorySelect = document.getElementById('category_select');
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

            //  Fetch equipment options with cat
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
