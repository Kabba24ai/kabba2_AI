@extends('admin.layouts.app')

@section('title', 'Terms and Condition')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Page Header --}}
    <div class="flex items-center space-x-3 mb-6">
        <x-heroicon-o-document-text class="h-8 w-8 text-blue-600" />
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 dark:text-white/90">Terms &amp; Conditions</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400">Manage the rental agreement header and the terms shown on customer agreements</p>
        </div>
    </div>

    {{-- ── Card 1: Rental Agreement Header (configuration) ─────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5 mb-6">
        <div class="flex items-center space-x-2 mb-1">
            <x-heroicon-o-pencil-square class="h-5 w-5 text-blue-600" />
            <h3 class="text-lg font-bold text-gray-900 dark:text-white/90">Rental Agreement Header</h3>
        </div>
        <p class="text-sm text-gray-500 dark:text-gray-400 mb-5">
            These three lines appear at the top of every customer rental agreement (order signing page).
        </p>

        <form method="POST" action="{{ route('admin.terms-and-conditions.header.update') }}" data-parsley-validate>
            @csrf
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Terms &amp; Conditions Line 1
                    </label>
                    {!! html()->text('terms_condition_text_1', old('terms_condition_text_1', $headerSettings['terms_condition_text_1'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none dark:bg-gray-800 dark:text-white dark:border-gray-600')
                        ->attributes(['placeholder' => 'Example: Rent \'n King Rental Agreement', 'maxlength' => '255']) !!}
                    @error('terms_condition_text_1')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Terms &amp; Conditions Line 2
                    </label>
                    {!! html()->text('terms_condition_text_2', old('terms_condition_text_2', $headerSettings['terms_condition_text_2'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none dark:bg-gray-800 dark:text-white dark:border-gray-600')
                        ->attributes(['placeholder' => 'Example: www.RentnKing.com', 'maxlength' => '255']) !!}
                    @error('terms_condition_text_2')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Terms &amp; Conditions Line 3
                    </label>
                    {!! html()->text('terms_condition_text_3', old('terms_condition_text_3', $headerSettings['terms_condition_text_3'] ?? ''))
                        ->class('w-full border border-gray-300 rounded-md px-3 py-3 text-sm shadow-sm focus:ring-2 focus:outline-none dark:bg-gray-800 dark:text-white dark:border-gray-600')
                        ->attributes(['placeholder' => 'Example: Development 360, Inc', 'maxlength' => '255']) !!}
                    @error('terms_condition_text_3')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="flex items-end justify-between gap-4 flex-wrap">
                    <a href="{{ asset('storage/admin/images/samples/terms-layout-preview.png') }}" target="_blank"
                        class="inline-flex items-center gap-2 px-4 py-2.5 border border-gray-300 rounded-md bg-white hover:bg-gray-50 text-sm font-medium text-gray-700 shadow-sm dark:bg-gray-800 dark:text-gray-200 dark:border-gray-600 dark:hover:bg-gray-700">
                        <x-heroicon-o-eye class="h-4 w-4" />
                        Preview Rental Agreement
                    </a>
                    <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2.5 rounded-md text-sm font-medium shadow-sm">
                        Save Header
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- ── Card 2: Terms Library (content management) ───────────────────────── --}}
    <div class="bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-100 dark:border-gray-800 p-5">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-5 gap-4">
            <div class="flex items-center space-x-2">
                <x-heroicon-o-rectangle-stack class="h-5 w-5 text-blue-600" />
                <h3 class="text-lg font-bold text-gray-900 dark:text-white/90">Terms Library</h3>
            </div>
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
        function fetchTerms(page = 1, perPage = 30) {
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
