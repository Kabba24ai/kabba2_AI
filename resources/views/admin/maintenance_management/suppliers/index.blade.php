@extends('admin.layouts.app')

@section('title', 'Supplier Management')

@section('content')
<div class="min-h-screen bg-gray-50">
    {{-- Header --}}
    <div class="bg-white border-b border-gray-200">
        <div class="px-6 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2">
                        <svg class="h-7 w-7 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-4m-5 0H3m2 0h3M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                        Supplier Management
                    </h1>
                    <p class="text-gray-600 mt-1">Manage your supplier database and relationships</p>
                </div>
                <a href="{{ route('admin.maintenance-management.suppliers.create') }}" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg flex items-center gap-2 transition-colors">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add Supplier
                </a>
            </div>
        </div>
    </div>

    {{-- Search --}}
    <div class="px-6 py-4 bg-white border-b border-gray-200">
        <form method="GET" action="{{ route('admin.maintenance-management.suppliers.index') }}" class="flex gap-4 items-end">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Suppliers</label>
                <div class="relative w-[300px]">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Search by name, contact, email..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Products</label>
                <div class="relative w-[300px]">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <input
                        type="text"
                        name="product_search"
                        value="{{ request('product_search') }}"
                        placeholder="Search by product type (e.g., bucket teeth)..."
                        class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    />
                </div>
            </div>

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg">
                Search
            </button>
            
            @if(request()->hasAny(['search', 'product_search']))
                <a href="{{ route('admin.maintenance-management.suppliers.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Clear
                </a>
            @endif
        </form>
    </div>

    {{-- Stats --}}
    <div class="px-6 py-4 bg-white border-b border-gray-200">
        <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
            <div class="bg-purple-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-purple-600">{{ $suppliers->total() }}</div>
                <div class="text-sm text-purple-600">Total Suppliers</div>
            </div>
            <div class="bg-green-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-green-600">{{ $stats['active_suppliers'] }}</div>
                <div class="text-sm text-green-600">Active Suppliers</div>
            </div>
            <div class="bg-blue-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-blue-600">0</div>
                <div class="text-sm text-blue-600">Product Types</div>
            </div>
            <div class="bg-orange-50 p-4 rounded-lg">
                <div class="text-2xl font-bold text-orange-600">{{ $stats['total_parts'] }}</div>
                <div class="text-sm text-orange-600">Total Parts</div>
            </div>
        </div>
    </div>

    @include('admin.maintenance_management.suppliers.partials._table')
</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    let searchInput = document.querySelector('input[name="search"]');
    //let status = document.querySelector('select[name="status"]');
    let productSearch = document.querySelector('input[name="product_search"]');
    let loader = document.querySelector('#suppliers-loading');
    let wrapper = document.querySelector('#suppliers-table-wrapper');
    let timeout = null;

    function fetchSuppliers() {
        const search = searchInput.value;
        const product_search = productSearch.value;

        const params = new URLSearchParams();
        if (search.length >= 3 || search.length === 0) params.append('search', search);
        if (product_search) params.append('product_search', product_search);

        // Show loader
        loader.classList.remove('hidden');
        wrapper.classList.add('opacity-50', 'pointer-events-none');

        fetch("{{ route('admin.maintenance-management.suppliers.index') }}?" + params.toString(), {
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
                console.error('Error fetching parts:', error);
            })
            .finally(() => {
                loader.classList.add('hidden');
                wrapper.classList.remove('opacity-50', 'pointer-events-none');
            });
    }


    // Debounce search input
    searchInput.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(fetchSuppliers, 400); // Wait 400ms before firing
    });
    productSearch.addEventListener('change', fetchSuppliers);
});
</script>

@endsection
