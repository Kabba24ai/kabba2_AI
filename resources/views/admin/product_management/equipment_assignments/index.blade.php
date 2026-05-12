@extends('admin.layouts.app')

@section('title', 'Equipment Assignments')

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Equipment Assignments</h3>
        <a href="{{ route('admin.product-management.equipment-assignments.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + New Assignment
        </a>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center mb-6">
        <div class="w-full sm:w-auto">
            <button type="button" id="clear-filters"
                class="text-sm text-gray-600 bg-white px-4 py-3 flex gap-2 items-center rounded-md border border-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Clear
            </button>
        </div>

        <div class="relative w-full sm:w-56">
            <input type="text" name="search" placeholder="Search by product..." value="{{ request('search') }}"
                class="w-full pl-3 pr-10 py-3 px-3 bg-white border border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500" />
            <div class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8" />
                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                </svg>
            </div>
        </div>

        <div class="w-full sm:w-52">
            <select name="category"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Category</option>
                @foreach ($categories as $id => $category)
                    <option value="{{ $id }}" @selected(request('category') == $id)>{{ $category }}</option>
                @endforeach
            </select>
        </div>

        <div class="w-full sm:w-44">
            <select name="status"
                class="w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </div>
    </div>

    <div id="assignment-table-wrapper">
        @include('admin.product_management.equipment_assignments.partials._table', ['assignments' => $assignments])
    </div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.querySelector('input[name="search"]');
        const categorySelect = document.querySelector('select[name="category"]');
        const statusSelect = document.querySelector('select[name="status"]');
        const perPageSelect = document.querySelector('select[name="per_page"]');
        const wrapper = document.querySelector('#assignment-table-wrapper');
        let timeout = null;

        function bindDeleteButtons() {
            wrapper.querySelectorAll('.delete-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const deleteUrl = this.dataset.url || `/admin/product-management/equipment-assignments/${this.dataset.id}`;
                    const rowId = this.dataset.id;

                    window.showConfirm(
                        'Delete this equipment assignment? This action cannot be undone!',
                        'Delete Equipment Assignment'
                    ).then((result) => {
                        if (!result.isConfirmed) {
                            return;
                        }

                        fetch(deleteUrl, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'Accept': 'application/json'
                                }
                            })
                            .then(async (response) => {
                                const data = await response.json().catch(() => ({}));
                                if (!response.ok || !data.success) {
                                    throw new Error(data.message || 'Failed to delete equipment assignment.');
                                }
                                return data;
                            })
                            .then((data) => {
                                if (typeof notyf !== 'undefined') {
                                    notyf.success(data.message || 'Equipment assignment deleted successfully.');
                                }

                                const row = document.getElementById(`assignment-row-${rowId}`);
                                if (row) {
                                    row.remove();
                                }

                                fetchAssignments(1);
                            })
                            .catch((error) => {
                                if (typeof notyf !== 'undefined') {
                                    notyf.error(error.message || 'Unable to delete equipment assignment.');
                                }
                            });
                    });
                });
            });
        }

        function fetchAssignments(page = 1) {
            const params = new URLSearchParams();
            if (searchInput.value.length >= 2 || searchInput.value.length === 0) {
                params.append('search', searchInput.value);
            }
            if (categorySelect.value) {
                params.append('category', categorySelect.value);
            }
            if (statusSelect.value) {
                params.append('status', statusSelect.value);
            }
            if (perPageSelect && perPageSelect.value) {
                params.append('per_page', perPageSelect.value);
            }
            params.append('page', page);

            wrapper.classList.add('opacity-50', 'pointer-events-none');

            fetch("{{ route('admin.product-management.equipment-assignments.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.text())
                .then(html => {
                    wrapper.innerHTML = html;
                    bindDeleteButtons();
                })
                .catch(() => {
                    wrapper.innerHTML = '<div class="text-red-500 p-4">Something went wrong loading the data.</div>';
                })
                .finally(() => {
                    wrapper.classList.remove('opacity-50', 'pointer-events-none');
                });
        }

        document.getElementById('clear-filters').addEventListener('click', function() {
            searchInput.value = '';
            categorySelect.value = '';
            statusSelect.value = '';
            if (perPageSelect) {
                perPageSelect.value = '15';
            }
            fetchAssignments();
        });

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => fetchAssignments(1), 400);
        });

        categorySelect.addEventListener('change', () => fetchAssignments(1));
        statusSelect.addEventListener('change', () => fetchAssignments(1));
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => fetchAssignments(1));
        }

        wrapper.addEventListener('click', function(event) {
            const link = event.target.closest('.pagination a');
            if (!link) {
                return;
            }

            event.preventDefault();
            const url = new URL(link.href);
            const page = url.searchParams.get('page') || 1;
            fetchAssignments(page);
        });

        bindDeleteButtons();
    });
</script>
@endsection
