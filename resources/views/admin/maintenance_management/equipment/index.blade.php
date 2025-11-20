@extends('admin.layouts.app')

@section('title', 'Equipment List')

@section('content')
<div class="h-screen bg-gray-50 flex flex-col overflow-hidden">
    <div class="flex-1 p-6 overflow-auto">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <svg class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Equipment Management</h1>
            </div>
            <p class="text-gray-600">Manage equipment inventory, assignments, and maintenance schedules</p>
        </div>


        @include('admin.maintenance_management.equipment.partials._stats')
        @include('admin.maintenance_management.equipment.partials._filters')
        <div id="equipment-table-wrapper" aria-live="polite">
            @include('admin.maintenance_management.equipment.partials._table')
        </div>
    </div>
    <!-- Checklist Master Assign Modal -->
    <div id="checklistMasterAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold"> Checklist Master : <span id="checklistMasterAssignModalTitle" class="capitalize"></span></h2>
                <button type="button"
                    class="close-checklist-master-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'checklistMasterAssignForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="checklist_master_unique_id">Checklist Master</label>
                    <select name="checklist_master_unique_id" id="checklist_master_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700" required>
                        <option value="" data-current-status="">Select Checklist Master</option>
                    </select>
                </div>
                <input type="hidden" id="equipment-unique-id" name="equipment_unique_id" value="">
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-checklist-master-assign-modal px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="checklist-master-assign-submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Store Assign Modal -->
    <div id="storeAssignModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 class="text-lg font-semibold"> Store : <span id="storeAssignModalTitle" class="capitalize"></span></h2>
                <button type="button"
                    class="close-store-assign-modal text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'storeAssignForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="store_unique_id">Store</label>
                    <select name="store_unique_id" id="store_unique_id"
                        class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700" required>
                        <option value="" data-current-status="">Select Store</option>
                        @foreach($stores as $uniqueId => $storeName)
                        <option value="{{ $uniqueId }}">{{ $storeName }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-store-assign-modal px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit" id="store-assign-submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Assign
                </button>
            </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('js')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        let searchInput = document.querySelector('input[name="search"]');
        let categorySelect = document.querySelector('select[name="category"]');
        let checklistMasterSelect = document.querySelector('select[name="checklist_master"]');
        let locationStore = document.querySelector('select[name="location_store"]');
        // let serviceDue = document.querySelector('select[name="serviceDue"]');
        // let rentalReady = document.querySelector('select[name="rentalReady"]');
        // let equipService = document.querySelector('select[name="equipService"]');
        let wrapper = document.querySelector('#equipment-table-wrapper');
        let timeout = null;
        const perPage = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;

        function fetchEquipments() {
            const search = searchInput.value;
            const category = categorySelect.value;
            const checklistMasterValue = checklistMasterSelect.value;
            const locationStoreValue = locationStore.value;

            // const serviceDueValue = serviceDue.value;
            // const rentalReadyValue = rentalReady.value;
            // const equipServiceValue = equipService.value;

            const params = new URLSearchParams();
            if (search.length >= 3 || search.length === 0) params.append('search', search);
            if (category) params.append('category', category);
            if (checklistMasterValue) params.append('checklist_master', checklistMasterValue);

            if (locationStoreValue) params.append('location_store', locationStoreValue);

            if (perPage) params.append('per_page', perPage);

            // Show loader
            wrapper.classList.add('opacity-50', 'pointer-events-none');

            apiFetch("{{ route('admin.maintenance-management.equipment.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, {
                    loaderSelector: '#equipment-loading',
                    containerSelector: '#equipment-table-wrapper'
                })
                .then(response => {
                    wrapper.innerHTML = response.html;
                })
        }


        // Debounce search input
        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchEquipments, 400); // Wait 400ms before firing
        });

        // Instant change on selects
        categorySelect.addEventListener('change', fetchEquipments);
        checklistMasterSelect.addEventListener('change', fetchEquipments);
        locationStore.addEventListener('change', fetchEquipments);

        //status.addEventListener('change', fetchEquipments);
        // serviceDue.addEventListener('change', fetchEquipments);
        // rentalReady.addEventListener('change', fetchEquipments);
        // equipService.addEventListener('change', fetchEquipments);

        const modal = document.getElementById('checklistMasterAssignModal');
        const checklistMasterAssignForm = document.getElementById('checklistMasterAssignForm');
        const checklistMasterUniqueId = document.getElementById('checklist_master_unique_id');
        const assignBtn = document.getElementById('checklist-master-assign-submit');
        const closeModalButtons = document.querySelectorAll('.close-checklist-master-assign-modal');
        const equipmentUniqueIdInput = document.getElementById('equipment-unique-id');
        const checklistMasterAssignModalTitle = document.getElementById('checklistMasterAssignModalTitle');
        // --- Event delegation for OPEN buttons (works after table refresh) ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.checklist-master-assign-btn');
            if (!btn) return;

            const equipmentUniqueId = btn.getAttribute('data-equipment-unique-id');
            equipmentUniqueIdInput.value = equipmentUniqueId;
            checklistMasterAssignModalTitle.textContent = btn.getAttribute('data-equipment-name') || '';
            modal.classList.remove('hidden');
        });

        // --- Event delegation for CLOSE buttons (works after table refresh) ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.close-checklist-master-assign-modal');
            if (!btn) return;
            clearModalFields();
            modal.classList.add('hidden');
        });

        function clearModalFields() {
            if (checklistMasterUniqueId) checklistMasterUniqueId.selectedIndex = 0;
            if (equipmentUniqueIdInput) equipmentUniqueIdInput.value = '';
            checklistMasterAssignModalTitle.textContent = '';
        }

        // Form submit
        checklistMasterAssignForm?.addEventListener('submit', function(e) {
            e.preventDefault();

            if (window.$ && $(checklistMasterAssignForm).parsley && !$(checklistMasterAssignForm).parsley()
                .isValid()) {
                $(checklistMasterAssignForm).parsley().validate();
                return;
            }

            const submitBtn = document.getElementById('checklist-master-assign-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Assigning...';
            }

            const formData = new FormData(checklistMasterAssignForm);

            apiFetch('{{ route('admin.maintenance-management.equipment.checklist-master-assign') }}', {
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
                        fetchChecklistMasters();
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

        fetchChecklistMasters();
        // Fetch checklist master options
        function fetchChecklistMasters() {
            apiFetch('{{ route('admin.checklist-management.checklist-master.fetch') }}')
                .then(data => {
                    if (data?.success) {
                        checklistMasterUniqueId.innerHTML = '';
                        const defaultOption = document.createElement('option');
                        defaultOption.textContent = 'Select Checklist Master';
                        defaultOption.disabled = true;
                        defaultOption.selected = true;
                        checklistMasterUniqueId.appendChild(defaultOption);
                        data.checklistMasters.forEach(checklistMaster => {
                            const option = document.createElement('option');
                            option.value = checklistMaster.unique_id;
                            option.textContent = checklistMaster.checklist_system_name;
                            checklistMasterUniqueId.appendChild(option);
                        });
                    }
                });
        };

        const storeAssignModal = document.getElementById('storeAssignModal');
        const storeAssignForm = document.getElementById('storeAssignForm');
        const storeUniqueId = document.getElementById('store_unique_id');
        const storeAssignBtn = document.getElementById('store-assign-submit');
        const storeAssignModalTitle = document.getElementById('storeAssignModalTitle');

        // --- Event delegation for OPEN buttons (works after table refresh) ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.store-assign-btn');
            if (!btn) return;
            const equipmentUniqueId = btn.getAttribute('data-equipment-unique-id');
            storeAssignBtn.setAttribute('data-equipment-unique-id', equipmentUniqueId);
            storeAssignModalTitle.textContent = btn.getAttribute('data-equipment-name') || '';
            storeAssignModal.classList.remove('hidden');
        });

        // --- Event delegation for CLOSE buttons (works after table refresh) ---
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('.close-store-assign-modal');
            if (!btn) return;
            clearStoreModalFields();
            storeAssignModal.classList.add('hidden');
        });

        function clearStoreModalFields() {
            if (storeUniqueId) storeUniqueId.selectedIndex = 0;
            storeAssignBtn.removeAttribute('data-equipment-unique-id');
            storeAssignModalTitle.textContent = '';
        }

        // Form submit
        storeAssignForm?.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.$ && $(storeAssignForm).parsley && !$(storeAssignForm).parsley()
                .isValid()) {
                $(storeAssignForm).parsley().validate();
                return;
            }

            const submitBtn = document.getElementById('store-assign-submit');
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Assigning...';
            }

            const formData = new FormData(storeAssignForm);
            const equipmentUniqueId = storeAssignBtn.getAttribute('data-equipment-unique-id');
            formData.append('equipment_unique_id', equipmentUniqueId);

            apiFetch('{{ route('admin.maintenance-management.equipment.store-assign') }}', {
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
                        storeAssignModal.classList.add('hidden');
                        if (window.notyf) notyf.success(data.message);
                        clearStoreModalFields();
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
    });
</script>
@endpush
