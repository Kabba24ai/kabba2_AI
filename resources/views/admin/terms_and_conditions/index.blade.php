@extends('admin.layouts.app')

@section('title', 'Terms and Condition')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Terms</h3>
        <a href="{{ route('admin.terms-and-conditions.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Terms
        </a>
    </div>

    {{-- Search and Filters --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 space-y-3 sm:space-y-0 mb-6">

        <div class="w-full sm:w-auto">
            <button type="button" id="clear-filters"
                class="text-sm text-gray-600 bg-white px-4 py-2 flex gap-2 items-center rounded-md border border-gray-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                    </path>
                </svg>
                Clear
            </button>
        </div>

        {{-- Search Input --}}
        <div class="flex-1 relative">
            <input type="text" name="search" placeholder="Search terms..." value="{{ request('search') }}"
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
        <select name="is_global" id="is_global"
            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
            <option value="">All Terms</option>
            <option value="Yes" @selected(request('is_global') === 'Yes')>Global</option>
            <option value="No" @selected(request('is_global') === 'No')>Product Specific</option>
        </select>
    </div>

    <div id="terms-table-wrapper" aria-live="polite">
        @include('admin.terms_and_conditions.partials._table', ['terms' => []])
    </div>
@endsection

@push('js')
    <script>
    document.addEventListener("DOMContentLoaded", function() {

        let wrapper = document.querySelector('#terms-table-wrapper');
        const isGlobalInput = document.querySelector('select[name="is_global"]');
        const searchInput = document.querySelector('input[name="search"]');
        const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search)
            .get('per_page') || null;
        const pageParam = new URLSearchParams(window.location.search).get('page') || 1;
        let timeout = null;

        const screenKey = 'terms_filters';

        const fieldMap = {
            'is_global': isGlobalInput,
            'search': searchInput,
        };

        // Load saved filters on page load
        FilterFreezer.loadFilters(screenKey, fieldMap);

        // Clear filters functionality using global clearFilters
        document.getElementById('clear-filters').addEventListener('click', function() {
            window.clearFilters(fieldMap, screenKey);
            fetchTerms();
        });

        fetchTerms(pageParam, perPageParam); // Initial fetch on page load
        function fetchTerms(page = 1, perPage = 10) {
            const params = new URLSearchParams();

            const isGlobal = isGlobalInput?.value || '';
            const search = searchInput?.value || '';

            if (isGlobal) params.append('is_global', isGlobal);
            if (search) params.append('search', search);
            if (perPage) params.append('per_page', perPage);
            params.set('page', page);

            // save current filters
            FilterFreezer.saveFilters(screenKey, fieldMap);

            wrapper.classList.add('opacity-50', 'pointer-events-none');

            apiFetch("{{ route('admin.terms-and-conditions.index') }}?" + params.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                }, {
                    loaderSelector: '#terms-loading',
                    containerSelector: '#terms-table-wrapper'
                })
                .then(response => {
                    wrapper.innerHTML = response.html;
                })
        }

        // Register pagination
        Paginator.init({
            wrapper: wrapper,
            fetchCallback: fetchTerms
        });

        // Event listeners for filters
        if (isGlobalInput) isGlobalInput.addEventListener('change', function() {
            fetchTerms();
        });

        if (searchInput) searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(fetchTerms, 400);
        });
    });

    </script>
@endpush
