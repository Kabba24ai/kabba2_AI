@extends('admin.layouts.app')

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

        {{-- Success Message --}}
        @if(session('success'))
            <div class="mx-0 mb-4 p-4 bg-green-50 border border-green-200 rounded-lg flex items-center space-x-3">
                <svg class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span class="text-green-800 font-medium">{{ session('success') }}</span>
            </div>
        @endif

        @include('admin.maintenance_management.equipment.partials._stats')
        @include('admin.maintenance_management.equipment.partials._filters')
        @include('admin.maintenance_management.equipment.partials._table')
    </div>
</div>
@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.querySelector('input[name="search"]');
            let categorySelect = document.querySelector('select[name="category"]');
            //let status = document.querySelector('select[name="status"]');
            let serviceDue = document.querySelector('select[name="serviceDue"]');
            let rentalReady = document.querySelector('select[name="rentalReady"]');
            let equipService = document.querySelector('select[name="equipService"]');
            let loader = document.querySelector('#equipment-loading');
            let wrapper = document.querySelector('#equipment-table-wrapper');
            let timeout = null;

            function fetchEquipments() {
                const search = searchInput.value;
                const category = categorySelect.value;
                const serviceDueValue = serviceDue.value;
                const rentalReadyValue = rentalReady.value;
                const equipServiceValue = equipService.value;

                const params = new URLSearchParams();
                if (search.length >= 3 || search.length === 0) params.append('search', search);
                if (category) params.append('category', category);
                if (serviceDueValue) params.append('serviceDue', serviceDueValue);

                // Show loader
                loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch("{{ route('admin.maintenance-management.equipments.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(response => response.text())
                    .then(html => {
                        wrapper.innerHTML = html;
                    })
                    .catch(error => {
                        wrapper.innerHTML =
                            '<div class="text-red-500 p-4">Something went wrong loading the data.</div>';
                        console.error('Error fetching equipments:', error);
                    })
                    .finally(() => {
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }


            // Debounce search input
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchEquipments, 400); // Wait 400ms before firing
            });

            // Instant change on selects
            categorySelect.addEventListener('change', fetchEquipments);
           //status.addEventListener('change', fetchEquipments);
            serviceDue.addEventListener('change', fetchEquipments);
            rentalReady.addEventListener('change', fetchEquipments);
            equipService.addEventListener('change', fetchEquipments);
        });
    </script>
@endpush
