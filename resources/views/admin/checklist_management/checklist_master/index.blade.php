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
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Create New Checklist System
                </a>
            </div>

        </div>
    </div>




    <div class="bg-white border border-gray-200 rounded-md p-4 w-full mt-6">
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="w-full sm:w-auto">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Clear Filters</label>
                <button type="button" id="clear-filters"
                    class="text-sm text-gray-600 bg-white px-4 py-2 flex gap-2 items-center rounded-md border border-gray-300">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                    Clear
                </button>
            </div>

            <div class="md:col-span-4">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Search Systems</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z" />
                        </svg>
                    </div>
                    <input type="text" placeholder="Search checklist systems..."
                        class="w-full border pl-10 pr-3 py-2 rounded-md text-sm" />
                </div>
            </div>


            <div class="md:col-span-4">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Filter by Category</label>
                {!! html()->select('equipment_category', ['' => 'All Categories'] + $equipmentCategories)->class('w-full border px-3 py-2 rounded-md text-sm')->attribute('id', 'categoryFilter') !!}
            </div>


            <div class="md:col-span-3">
                <label class="text-sm font-medium text-gray-700 mb-1 block">Results</label>
                <div class="flex items-center gap-2 border rounded-md px-3 py-2 text-sm">
                    <svg class="w-5 h-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                    </svg>

                    <span>
                        {{ $checklistMasters->count() }} of {{ $checklistMasters->count() }}
                        {{ Str::plural('system', $checklistMasters->count()) }}
                    </span>
                </div>
            </div>
        </div>
    </div>


    <div id="checklist-wrapper">
        @include('admin.checklist_management.checklist_master.partials._table', ['checklistMasters' => []])
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

                    const templateName = form.getAttribute('data-checklist-master-name') ||
                        'this checklist master';

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
        document.addEventListener("DOMContentLoaded", () => {

            const screenKey = "checklist_master_filters";
            const categoryFilter = document.getElementById('categoryFilter');
            const searchInput = document.querySelector('input[placeholder="Search checklist systems..."]');
            const wrapper = document.querySelector('#checklist-wrapper');
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location
                    .search)
                .get('per_page') || null;

            let timeout = null;

            const fieldMap = {
                'categoryFilter': categoryFilter,
                'search': searchInput,
            };

            FilterFreezer.loadFilters(screenKey, fieldMap);

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchList(1, perPageParam);
            });

            function fetchList(page = 1, perPage = 10) {
                const params = new URLSearchParams();
                params.append('page', page);
                params.append('per_page', perPage);
                params.append('categoryFilter', categoryFilter.value);
                params.append('search', searchInput.value);

                wrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch(`{{ route('admin.checklist-management.checklist-master.index') }}?${params.toString()}`, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        wrapper.innerHTML = data.html;

                        FilterFreezer.saveFilters(screenKey, fieldMap);
                        bindPagination();
                    })
                    .catch(() => {
                        wrapper.innerHTML = '<div class="text-red-500 p-4">Error loading data.</div>';
                    })
                    .finally(() => {
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            function bindPagination() {
                wrapper.querySelectorAll('.pagination a').forEach(a => {
                    a.addEventListener('click', function(e) {
                        e.preventDefault();
                        const url = new URL(this.href);
                        const page = url.searchParams.get('page');
                        const perPage = url.searchParams.get('per_page');
                        fetchList(page, perPage);
                    });
                });
            }

            function filtersUpdated() {
                clearTimeout(timeout);
                timeout = setTimeout(() => fetchList(1, perPageParam), 400);
            }

            // categoryFilter.addEventListener('change', () => fetchList());

            categoryFilter.addEventListener('change', function() {
                fetchList(1, perPageParam);
            });

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(() => fetchList(1, perPageParam), 400);
            });

            // searchInput.addEventListener('input', filtersUpdated);

            // Initial load
            fetchList(
                new URLSearchParams(location.search).get('page') || 1,
                new URLSearchParams(location.search).get('per_page') || 10
            );
        });
    </script>
@endpush
