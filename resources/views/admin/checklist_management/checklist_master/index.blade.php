@extends('admin.layouts.app')

@section('title', 'checklist master')

@push('css')

@endpush

@section('content')

@include('flash::message')

<div class="">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">

        <!-- Left: Title and Subtitle -->
        <div>
            <h2 class="text-2xl font-semibold text-gray-900">Checklist Systems</h2>
            <p class="text-sm text-gray-600 mt-1">
                Independent checklist systems that can be assigned to multiple equipment items
            </p>
        </div>

        <!-- Right: Action Button -->
        <div>
            <a href="{{ route('admin.checklist-management.checklist-master.create') }}"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition">
                <!-- Plus Icon -->
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Create New Checklist System
            </a>
        </div>

    </div>
</div>


<!-- Filters -->
<div class="bg-white border border-gray-200 rounded-md p-4 w-full mt-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Search Systems</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                    </svg>
                </div>
                <input type="text" placeholder="Search checklist systems..." class="w-full border pl-10 pr-3 py-2 rounded-md text-sm" />
            </div>
        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Filter by Category</label>

            {!! html()->select(
            'equipment_category',
            ['' => 'All Categories'] + $equipmentCategories
            )
            ->class('w-full border px-3 py-2 rounded-md text-sm')
            ->attribute('id', 'categoryFilter')
            ->attribute('required', true)
            ->attribute('data-parsley-required-message', 'Equipment category is required.')
            !!}

        </div>
        <div>
            <label class="text-sm font-medium text-gray-700 mb-1 block">Results</label>
            <div class="border rounded-md px-3 py-2 text-sm flex items-center gap-2">
                <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>

                {{ $checklistMasters->count() }} of {{ $checklistMasters->count() }} {{ Str::plural('system', $checklistMasters->count()) }}
            </div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="bg-white border border-gray-200 rounded-md overflow-x-auto mt-6">
    <table class="min-w-full divide-y divide-gray-200 text-sm whitespace-nowrap">
        <thead class="bg-gray-100 text-gray-600">
            <tr>
                <th class="px-4 py-3 text-left font-semibold">Checklist System Name</th>
                <th class="px-4 py-3 text-left font-semibold">Category</th>
                <th class="px-4 py-3 text-left font-semibold">Rental Ready</th>
                <th class="px-4 py-3 text-left font-semibold">Customer Checklist</th>
                <th class="px-4 py-3 text-left font-semibold">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-900">

            @foreach ($checklistMasters as $Master)

            <tr data-status="{{ $Master->equipment_category_id }}">
                <td class="px-4 py-3 font-semibold text-gray-900">{{ $Master->checklist_system_name }}</td>
                <td class="px-4 py-3"><span class="bg-gray-100 px-2 py-1 rounded-full text-xs font-medium">{{ $Master->category->getHierarchyLabel() }}</span></td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-blue-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900"> {{ $Master->rentalReadyTemplate?->questions?->count() ?? 0 }}
                                {{ Str::plural('question', $Master->rentalReadyTemplate?->questions?->count() ?? 0) }}
                            </div>
                            <a href="{{ route('admin.checklist-management.rental-ready.index', ['template' => $Master->rentalReadyTemplate->template_name ?? '']) }}" class="text-xs text-blue-600 hover:text-blue-800 hover:underline transition-colors"> {{ $Master->rentalReadyTemplate->template_name ?? 'No template assigned' }} </a>
                        </div>
                    </div>
                </td>
                <td class="px-4 py-3">
                    <div class="flex items-center gap-2">
                        <x-heroicon-o-document-text class="w-4 h-4 text-purple-600" />
                        <div class="text-left">
                            <div class="text-sm text-gray-900">
                                {{ $Master->customerAdminTemplate?->questions?->count() ?? 0 }}
                                {{ Str::plural('question', $Master->customerAdminTemplate?->questions?->count() ?? 0) }}
                            </div>

                            @if ($Master->customerAdminTemplate)
                            <a href="{{ route('admin.checklist-management.customer-admin.index', ['template' => $Master->customerAdminTemplate->template_name]) }}"
                                class="text-xs text-purple-600 hover:text-purple-800 hover:underline transition-colors"
                                title="Go to this Customer Admin Template">
                                {{ $Master->customerAdminTemplate->template_name }}
                            </a>
                            @else
                            <span class="text-xs text-gray-500">No template assigned</span>
                            @endif
                        </div>
                    </div>
                </td>


                <td class="px-4 py-3 ">
                    <div class="flex gap-2">

                   <form action="{{ route('admin.checklist-management.checklist-master.copy', $Master->unique_id) }}"
      method="POST"
      class="inline">
    @csrf
    <button type="submit"
        class="text-green-600 hover:text-green-800"
        title="Copy this Checklist Master">
        <x-heroicon-o-clipboard-document class="w-4 h-4" />
    </button>
</form>



                        <a href="{{ route('admin.checklist-management.checklist-master.edit',$Master->unique_id) }}" class="text-blue-600">
                            <x-heroicon-o-pencil class="w-4 h-4" />
                        </a>

                        <form action="{{ route('admin.checklist-management.checklist-master.delete', $Master->unique_id) }}"
                            method="POST"
                            class="inline delete-checklist-master-form"
                            data-checklist-master-name="{{ $Master->checklist_system_name }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                <x-heroicon-o-trash class="w-4 h-4" />
                            </button>
                        </form>



                    </div>
                </td>
            </tr>

            @endforeach
        </tbody>
    </table>
</div>

<div class="bg-blue-50 border border-blue-200 rounded-md p-4 mt-6">
    <div class="flex items-start space-x-3">
        <!-- Note icon with responsive sizing -->
        <x-heroicon-o-document-text class="w-4 h-5 sm:w-6 sm:h-6 md:w-4 md:h-7 text-blue-900 flex-shrink-0" />
        <!-- Text content -->
        <div>
            <h3 class="font-semibold text-blue-900 text-base sm:text-lg">System Assignment</h3>
            <p class="text-sm sm:text-sm text-blue-800 leading-snug mt-1">
                These checklist systems are independent and can be assigned to multiple equipment items
                (3, 5, 12, or more) through the Equipment Profile screen in a separate module.
                Each system combines both rental ready and customer checklists for complete equipment management.
            </p>
        </div>
    </div>
</div>



@endsection

@push('js')





<!-- delete- -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.delete-checklist-master-form').forEach(function(form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // stop auto submit

                const templateName = form.getAttribute('data-checklist-master-name') || 'this checklist master';

                window.showConfirm(
                    `Delete "${templateName}"? This action cannot be undone!`,
                    'Delete Checklist System'
                ).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    });
</script>

<!-- delete- -->


<script>
document.addEventListener("DOMContentLoaded", function () {

    /* ------------------------------------
        FREEZE FILTER (LOCAL STORAGE)
    ------------------------------------ */
    const screenKey = "checklist_master_filters";

    const categoryFilter = document.getElementById('categoryFilter');
    const searchInput = document.querySelector('input[placeholder="Search checklist systems..."]');
    const rows = document.querySelectorAll('tbody tr');
    const wrapper = document.querySelector('.bg-white.border.border-gray-200.rounded-md.overflow-x-auto.mt-6');

    const fieldMap = {
        'categoryFilter': categoryFilter,
        'search': searchInput,
    };

    // Load previously saved filters
    FilterFreezer.loadFilters(screenKey, fieldMap);



    /* ------------------------------------
        FILTER FUNCTION
    ------------------------------------ */
    function filterRows() {

        // Save filters on each change
        FilterFreezer.saveFilters(screenKey, fieldMap);

        wrapper.classList.add('opacity-50', 'pointer-events-none');

        const selectedCategory = String(categoryFilter.value || "");
        const searchQuery = (searchInput.value || "").toLowerCase();

        rows.forEach(row => {
            const category = String(row.getAttribute('data-status') || "");
            const systemName = row.querySelector('td')?.textContent.toLowerCase() || '';

            const matchCategory = (!selectedCategory || selectedCategory === category);
            const matchSearch = (!searchQuery || systemName.includes(searchQuery));

            row.style.display = (matchCategory && matchSearch) ? '' : 'none';
        });

        setTimeout(() => {
            wrapper.classList.remove('opacity-50', 'pointer-events-none');
        }, 200);
    }



    /* ------------------------------------
        BIND EVENTS
    ------------------------------------ */
    categoryFilter.addEventListener('change', filterRows);
    searchInput.addEventListener('input', filterRows);

    // Run once on initial load
    filterRows();

});
</script>

@endpush