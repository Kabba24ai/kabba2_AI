@extends('admin.layouts.app')

@section('title', 'Supplier Management')

@section('content')

{{-- Header --}}
<div class="">
    <div class="py-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

            <!-- Left Section -->
            <div>
                <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                    <svg class="h-7 w-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Supplier
                </h1>
                <p class="text-gray-600 mt-1 text-sm sm:text-base">Manage your supplier database</p>
            </div>


            <!-- Right Buttons -->
            <div class="flex flex-col sm:flex-row gap-2 w-full sm:w-auto">


                <!-- Manage Tags -->
                <a href="javascript:void(0)" onclick="openModal('TagModalWrapper')"
                    class="flex items-center justify-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-6 py-3 text-md rounded-lg transition-colors w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path
                            d="M12 2H2v10l9.29 9.29c.94.94 2.48.94 3.42 0l6.58-6.58c.94-.94.94-2.48 0-3.42L12 2Z" />
                        <path d="M7 7h.01" />
                    </svg>
                    Manage Tags
                </a>

                <!-- Add Supplier -->
                <a href="javascript:void(0)" onclick="openAddSupplierModal()"
                    class="flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 text-md rounded-lg transition-colors w-full sm:w-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M5 12h14"></path>
                        <path d="M12 5v14"></path>
                    </svg>
                    Add Supplier
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Search --}}
<div class="bg-white rounded-2xl p-4 sm:p-6 shadow-sm border border-gray-100 mb-6">
    <form method="GET" action="{{ route('admin.maintenance-management.suppliers.index') }}" class="flex flex-wrap gap-4 items-end">

        {{-- Name / Email --}}
        <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Name / Email</label>
            <div class="relative">
                <input type="text" name="search_name_email" value="{{ request('search_name_email') }}"
                    placeholder="Search by contact person or email..."
                    class="w-full pl-3 px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
        </div>

        {{-- Company --}}
        <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Company Search</label>
            <div class="relative">
                <input type="text" name="company_search" value="{{ request('company_search') }}"
                    placeholder="Search by company name..."
                    class="w-full pl-3 px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
        </div>

        {{-- Category Dropdown --}}
        <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
            <select id="supplierpartCategory" name="category" class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">All Categories</option>
                @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->title }}</option>
                                @endforeach
            </select>
        </div>

        {{-- Search by Part --}}
        <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Search by Part</label>
            <div class="relative">
                <input type="text" name="part_search" value="{{ request('part_search') }}"
                    placeholder="Search by part name..."
                    class="w-full pl-3 pr-4 px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
        </div>

        {{-- Tags --}}
        <!-- <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
            <div class="relative">
                <input type="text" name="tags_search" value="{{ request('tags_search') }}"
                    placeholder="Search by tags..."
                    class="w-full pl-3 pr-4 px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500" />
            </div>
        </div> -->

        {{-- Tags Filter --}}
{{-- Tags Filter --}}
<div class="w-full sm:w-48">
    <label class="block text-sm font-medium text-gray-700 mb-1">Tags</label>

    <select name="tag"
        id="tags_select"
        class="choices-select w-full rounded-md border border-gray-300 bg-white text-sm text-gray-900 shadow-sm focus:border-gray-500 focus:ring-1 focus:ring-blue-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
        <option value="">Select Tag</option>
    </select>
</div>






        {{-- Status Dropdown --}}
        <div class="w-full sm:w-48">
            <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
            <select name="status" class="w-full px-3 py-3 border border-gray-300 rounded-md text-sm text-gray-900 focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">All</option>
                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>

            </select>
        </div>

        {{-- Buttons --}}
        <div class="flex gap-2">

            <a href="{{ route('admin.maintenance-management.suppliers.index') }}" class="text-sm text-gray-600 bg-white px-3 py-3 rounded-md border border-gray-300">
                Clear All Filters
            </a>

        </div>
    </form>
</div>

@include('flash::message')
<!-- @include('admin.partials.formErrors') -->
@include('admin.partials.notify')




@include('admin.maintenance_management.suppliers.partials._table')


@include('admin.maintenance_management.suppliers.partials._model_category')
@include('admin.maintenance_management.suppliers.partials._model_tag')
@include('admin.maintenance_management.suppliers.partials._model_add_supplier')
@include('admin.maintenance_management.suppliers.partials._model_view_supplier')

@include('admin.maintenance_management.suppliers.partials._model_assign_part')



@endsection


@push('js')

<script>
    document.addEventListener('DOMContentLoaded', () => {

        // === Common Modal Functions ===
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.remove('hidden');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (modal) modal.classList.add('hidden');
        }


        // === Optional: Close when clicking outside modal content ===
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal-wrapper')) {
                e.target.classList.add('hidden');
            }
        });

        // === Make globally accessible ===
        window.openModal = openModal;
        window.closeModal = closeModal;
    });
</script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const inputs = document.querySelectorAll(
            'input[name="search_name_email"], input[name="company_search"], input[name="part_search"]'
        );
        const selects = document.querySelectorAll(
            'select[name="category"], select[name="status"] , select[name="tag"] '
        );
        const wrapper = document.querySelector('#supplier-table-wrapper');

        let timeout = null;
        const perPage = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;

        // --- Global function to fetch suppliers ---
        window.fetchSuppliers = function() {
            const params = new URLSearchParams();
            if (perPage) params.append('per_page', perPage);

            // Append input values
            inputs.forEach(input => {
                if (input.value.length >= 1 || input.value.length === 0) {
                    params.append(input.name, input.value);
                }
            });

            // Append select values
            selects.forEach(select => {
                if (select.value) {
                    params.append(select.name, select.value);
                }
            });

            // Add loader effect
            wrapper.classList.add('opacity-50', 'pointer-events-none');

            fetch(`{{ route('admin.maintenance-management.suppliers.index') }}?${params.toString()}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    wrapper.innerHTML = data.html;
                })
                .catch(err => {
                    wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading suppliers.</div>';
                    console.error(err);
                })
                .finally(() => {
                    wrapper.classList.remove('opacity-50', 'pointer-events-none');
                });
        };

        // --- Input listeners with debounce ---
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => {
                    window.fetchSuppliers();
                }, 400);
            });
        });

        // --- Select listeners (instant filter) ---
        selects.forEach(select => {
            select.addEventListener('change', window.fetchSuppliers);
        });

        // --- Global delete supplier function ---
        window.deleteSupplier = function(id, name) {
            window.showConfirm(
                `Are you sure you want to delete "${name}"?`,
                'Delete Supplier'
            ).then((result) => {
                if (result.isConfirmed) {
                    let deleteUrl = `{{ route('admin.maintenance-management.suppliers.delete', ['supplier' => ':id']) }}`;
                    deleteUrl = deleteUrl.replace(':id', id);

                    fetch(deleteUrl, {
                            method: 'DELETE',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                notyf.success(data.message);
                                window.fetchSuppliers(); // Refresh table after deletion
                            } else {
                                notyf.error(data.message || "Failed to delete supplier!");
                            }
                        })
                        .catch(() => notyf.error("Error deleting supplier!"));
                }
            });
        };
    });
</script>

@endpush
