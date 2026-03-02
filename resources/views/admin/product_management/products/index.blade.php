@extends('admin.layouts.app')

@section('title', 'Products')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Products</h3>
        <a href="{{ route('admin.product-management.products.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Product
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

        {{-- Search Input --}}
        <div class="w-full sm:w-48">
            <input type="text" name="search" placeholder="Search products..." value="{{ request('search') }}"
                class="w-full pl-3 pr-10 py-3 px-3 bg-white border border-gray-300 rounded-md text-sm w-full focus:ring-blue-500 focus:border-blue-500" />

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
                class="choices-select w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Category</option>
                @foreach ($categories as $id => $category)
                    <option value="{{ $id }}" @selected(request('category') == $id)>
                        {{ $category }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Select Price --}}
        <div class="w-full sm:w-48">
            <select name="price"
                class="choices-select w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">View Only</option>
                <option value="High Demand Alert">High Demand Alert</option>
                <option value="Sale Price">Sale Price</option>
                <option value="Regular Price">Regular Price</option>
            </select>
        </div>

        {{-- Type Dropdown --}}
        <div class="w-full sm:w-48">
            <select name="type"
                class="choices-select w-full h-10 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">All Products</option>
                <option value="Rental" @selected(request('type') === 'Rental')>Rental</option>
                <option value="Retail" @selected(request('type') === 'Retail')>Retail</option>
            </select>
        </div>

    </div>


    <div id="product-table-wrapper">
        @include('admin.product_management.products.partials._table', ['products' => []])
    </div>

@endsection

@push('js')
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            let searchInput = document.querySelector('input[name="search"]');
            let categorySelect = document.querySelector('select[name="category"]');
            let priceSelect = document.querySelector('select[name="price"]');
            let typeSelect = document.querySelector('select[name="type"]');
            let loader = document.querySelector('#product-loading');
            let wrapper = document.querySelector('#product-table-wrapper');
            let timeout = null;
            const perPageParam = document.getElementById('per_page_sm')?.value || new URLSearchParams(location.search)
                .get('per_page') || null;
            const pageParam = new URLSearchParams(window.location.search).get('page') || 1;

            const screenKey = 'product_filters';

            const fieldMap = {
                'search': searchInput,
                'category': categorySelect,
                'price': priceSelect,
                'type': typeSelect,
            };


            // Load saved filters on page load
            FilterFreezer.loadFilters(screenKey, fieldMap);

            // Clear filters functionality using global clearFilters
            document.getElementById('clear-filters').addEventListener('click', function() {
                window.clearFilters(fieldMap, screenKey);
                fetchProducts();
            });

            fetchProducts(pageParam, perPageParam); // initial fetch after loading saved filters
            function fetchProducts(page = 1, perPage = 10) {
                const search = searchInput.value;
                const category = categorySelect.value;
                const type = typeSelect.value;

                const params = new URLSearchParams();
                if (search.length >= 3 || search.length === 0) params.append('search', search);
                if (category) params.append('category', category);
                if (priceSelect.value) params.append('price', priceSelect.value);
                if (type) params.append('type', type);
                if (perPage) params.append('per_page', perPage);
                params.append('page', page);

                // Show loader
                loader.classList.remove('hidden');

                // Save current filters
                FilterFreezer.saveFilters(screenKey, fieldMap);

                wrapper.classList.add('opacity-50', 'pointer-events-none');

                fetch("{{ route('admin.product-management.products.index') }}?" + params.toString(), {
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
                        console.error('Error fetching products:', error);
                    })
                    .finally(() => {
                        loader.classList.add('hidden');
                        wrapper.classList.remove('opacity-50', 'pointer-events-none');
                    });
            }

            // Register pagination
            Paginator.init({
                wrapper: wrapper,
                fetchCallback: fetchProducts
            });

            // Debounce search input
            searchInput.addEventListener('input', function() {
                clearTimeout(timeout);
                timeout = setTimeout(fetchProducts, 400); // Wait 400ms before firing
            });

            // Instant change on selects
            categorySelect.addEventListener('change', fetchProducts);
            priceSelect.addEventListener('change', fetchProducts);
            highDemandSelect.addEventListener('change', fetchProducts);
            typeSelect.addEventListener('change', fetchProducts);
        });

    </script>
@endpush
