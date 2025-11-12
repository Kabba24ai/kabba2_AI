@extends('admin.layouts.app')

@section('title', 'Stores')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Stores</h3>
        <a href="{{ route('admin.stores.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Store
        </a>
    </div>

    {{-- Search and Filters --}}
    <form method="GET" action="{{ route('admin.stores.index') }}" class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {{-- Search Input --}}
            <div class="flex-1 relative">
                <input type="text" name="search" placeholder="Search by store name, email" value="{{ request('search') }}"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
            </div>

            {{-- Type Dropdown --}}
            <select name="status"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">All Status</option>
                <option value="Active" {{ request('status') == 'Active' ? 'selected' : '' }}>Active</option>
                <option value="Inactive" {{ request('status') == 'Inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="Archived" {{ request('status') == 'Archived' ? 'selected' : '' }}>Archived</option>
            </select>
        </div>
    </form>

    {{-- Stores Table --}}
    <div id="stores-table-wrapper" aria-live="polite">
        @include('admin.stores.partials._table', ['stores' => $stores])
    </div>

@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let searchInput = document.querySelector('input[name="search"]');
            let statusInput = document.querySelector('select[name="status"]');

            let loader = document.querySelector('#stores-loading');
            let wrapper = document.querySelector('#stores-table-wrapper');
            let timeout = null;
            const perPage = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search).get('per_page') || null;

            function fetchStores() {
                const search = searchInput.value;
                const status = statusInput.value;

                const params = new URLSearchParams();
                params.set('page', 1); // Always reset to first page on filter
                if (search.length >= 3 || search.length === 0) params.append('search', search);
                if (status) params.append('status', status);
                if (perPage) params.append('per_page', perPage);

                // Show loader
                loader.classList.remove('hidden');
                wrapper.classList.add('opacity-50', 'pointer-events-none');

                apiFetch("{{ route('admin.stores.index') }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => {
                        wrapper.innerHTML = response.html;
                        initOrderCheckboxes(); // Reinitialize checkboxes after new content
                    })
                    .finally(() => {
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });

            }

            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchStores, 400); // Wait 400ms before firing
            });

            // Instant change on selects
            statusInput.addEventListener('change', fetchStores);
        });
    </script>
@endpush
