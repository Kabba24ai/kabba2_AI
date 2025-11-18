@extends('admin.layouts.app')

@section('title', 'Equipment Inventory')

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
            <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Equipment Inventory </h3>
        </div>
        <a href="{{ route('admin.order-management.equipment-inventory.index') }}" id="reloadBtn"
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

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-6">

            {{-- Search Input --}}
            <div class="relative w-full sm:w-48">
                <input type="text" name="search" placeholder="Search equipment, ID, or customer" value="{{ request('search') }}"
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

        </div>
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
            <div class="flex flex-wrap items-center gap-6">
                <!-- Equipment Status -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
                    <svg class=" w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <span class="font-medium">Equipment Status</span>
                    <label class="flex items-center gap-1 ml-2">
                        <input type="checkbox" value="Available" name="equipment_status[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" {{ request('equipment_status') === null || in_array('Available', (array)request('equipment_status')) ? 'checked' : '' }}>
                        Available
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" value="Rented" name="equipment_status[]"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" {{ request('equipment_status') === null || in_array('Rented', (array)request('equipment_status')) ? 'checked' : '' }}>
                        Rented
                    </label>
                </div>

                <!-- Issues & Maintenance -->
                <div class="flex items-center gap-2 bg-white rounded-lg px-4 py-2 border shadow-sm flex-wrap md:flex-nowrap">
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
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" {{ request('equipment_status') === null || in_array('Maintenance', (array)request('equipment_status')) ? 'checked' : '' }}>
                        Maint. Hold
                    </label>
                    <label class="flex items-center gap-1">
                        <input type="checkbox" name="equipment_status[]" value="Damaged"
                            class="text-blue-600 focus:ring-blue-500 rounded border-gray-300" {{ request('equipment_status') === null || in_array('Damaged', (array)request('equipment_status')) ? 'checked' : '' }}>
                        Damaged
                    </label>
                </div>
            </div>
        </div>
    </div>


    <div class="mx-auto">
        <div id="equipment-table-wrapper">
            @include('admin.order_management.equipment_inventory.partials._table')
        </div>
    </div>

@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.querySelector('input[name="search"]');
            let categorySelect = document.querySelector('select[name="category"]');
            // let statusSelect = document.querySelector('select[name="status"]');
            let storeSelect = document.querySelector('select[name="store"]');
            let wrapper = document.querySelector('#equipment-table-wrapper');
            let equipmentStatusCheckboxes = document.querySelectorAll('input[name="equipment_status[]"]');
            let timeout = null;
            const perPage = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;

            function fetchEquipment() {
                const params = new URLSearchParams();
                if (searchInput.value.length >= 3 || searchInput.value === '') params.append('search', searchInput
                    .value);
                if (categorySelect.value) params.append('category', categorySelect.value);
                // if (statusSelect.value) params.append('status', statusSelect.value);
                if (storeSelect.value) params.append('store', storeSelect.value);
                if (perPage) params.append('per_page', perPage);

                // Add all checked equipment_status checkboxes
                equipmentStatusCheckboxes.forEach(cb => {
                    if (cb.checked) params.append('equipment_status[]', cb.value);
                });

                // loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch("{{ route('admin.order-management.equipment-inventory.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        wrapper.innerHTML = data.html;
                    })
                    .catch(err => {
                        wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading equipment.</div>';
                        console.error(err);
                    })
                    .finally(() => {
                        // loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            // Delayed search
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchEquipment, 400);
            });

            // Immediate filters
            // [categorySelect, statusSelect, storeSelect].forEach(el => el.addEventListener('change', fetchEquipment));
            [categorySelect, storeSelect].forEach(el => el.addEventListener('change', fetchEquipment));

            // Immediate filter for checkboxes
            equipmentStatusCheckboxes.forEach(cb => cb.addEventListener('change', fetchEquipment));

        });
    </script>

    <script>
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
