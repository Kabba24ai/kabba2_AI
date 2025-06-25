@extends('admin.layouts.app')

@section('title', 'Parts Management')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="p-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Parts Management</h1>
            </div>
            <p class="text-gray-600">Manage parts inventory across all equipment</p>
        </div>

        {{-- Stats Cards --}}
        <div class="grid grid-cols-5 gap-4 mb-6">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Total</p>
                        <p class="text-2xl font-bold text-gray-700 mt-1">{{ $parts->count() }}</p>
                    </div>
                    <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">In Stock</p>
                        <p class="text-2xl font-bold text-green-600 mt-1">{{ $parts->where('stock_status', 'in-stock')->count() }}</p>
                    </div>
                    <svg class="h-6 w-6 text-green-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Buy Now</p>
                        <p class="text-2xl font-bold text-yellow-600 mt-1">{{ $parts->where('stock_status', 'buy-now')->count() }}</p>
                    </div>
                    <svg class="h-6 w-6 text-yellow-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">Out of Stock</p>
                        <p class="text-2xl font-bold text-red-600 mt-1">{{ $parts->where('stock_status', 'out-of-stock')->count() }}</p>
                    </div>
                    <svg class="h-6 w-6 text-red-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>

            <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="text-left">
                        <p class="text-sm font-medium text-gray-600 uppercase tracking-wide">DNI</p>
                        <p class="text-2xl font-bold text-gray-700 mt-1">{{ $parts->where('stock_status', 'dni')->count() }}</p>
                    </div>
                    <svg class="h-6 w-6 text-gray-600 opacity-80" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Filter Controls --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="{{ route('admin.maintenance-management.parts.index') }}" class="flex items-center justify-between space-x-4">
                <div class="flex items-center space-x-4 flex-1">
                    {{-- Search --}}
                    <div class="relative max-w-sm">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search parts, equipment, suppliers..." 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                    </div>

                    {{-- Category Filter --}}
                    <select name="category" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[160px]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>

                    {{-- Equipment Filter --}}
                    <select name="equipment_id" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[200px]">
                        <option value="">All Equipment</option>
                        @foreach($equipmentOptions as $equipment)
                            <option value="{{ $equipment->equipment_id }}" {{ request('equipment_id') == $equipment->equipment_id ? 'selected' : '' }}>
                                {{ $equipment->equipment_name }} - {{ $equipment->equipment_id }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Stock Status Filter --}}
                    <select name="stock_status" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white min-w-[140px]">
                        <option value="">All Status</option>
                        <option value="in-stock" {{ request('stock_status') == 'in-stock' ? 'selected' : '' }}>In Stock</option>
                        <option value="buy-now" {{ request('stock_status') == 'buy-now' ? 'selected' : '' }}>Buy Now</option>
                        <option value="out-of-stock" {{ request('stock_status') == 'out-of-stock' ? 'selected' : '' }}>Out of Stock</option>
                        <option value="dni" {{ request('stock_status') == 'dni' ? 'selected' : '' }}>DNI</option>
                    </select>

                    <button type="submit" class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        Filter
                    </button>

                    @if(request()->hasAny(['search', 'category', 'equipment_id', 'stock_status']))
                        <a href="{{ route('admin.maintenance-management.parts.index') }}" class="px-4 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>

                {{-- Add Part Button --}}
                <a href="{{ route('admin.maintenance-management.parts.create') }}" class="flex items-center space-x-2 px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors flex-shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Add Part</span>
                </a>
            </form>
        </div>
      @include('admin.maintenance_management.parts.partials._table')
       
    </div>
</div>

{{-- Stock Update Modal --}}
<div id="stockModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4 hidden">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-bold text-gray-900">Update Stock</h2>
            <button onclick="closeStockModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="mb-4">
            <p class="text-sm font-medium text-gray-700 mb-1" id="partName"></p>
            <p class="text-xs text-gray-500" id="partNumber"></p>
        </div>

        <form id="stockForm" method="POST">
            @csrf
            @method('PATCH')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Quantity on Hand</label>
                <input type="number" id="stockLevel" name="stock_level" min="0" 
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center text-lg font-medium"
                       placeholder="0" required>
                <p class="text-xs text-gray-500 mt-1">Minimum stock: <span id="minStock"></span></p>
            </div>

            <div class="flex justify-end space-x-3">
                <button type="button" onclick="closeStockModal()" 
                        class="px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                    Update
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function updateStock(partId, partName, currentStock, minStock) {
    document.getElementById('partName').textContent = partName;
    document.getElementById('stockLevel').value = currentStock;
    document.getElementById('minStock').textContent = minStock;
    document.getElementById('stockForm').action = `/admin/parts/${partId}/stock`;
    document.getElementById('stockModal').classList.remove('hidden');
}

function closeStockModal() {
    document.getElementById('stockModal').classList.add('hidden');
}

document.getElementById('stockForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch(this.action, {
        method: 'PATCH',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
    });
});

document.addEventListener("DOMContentLoaded", function() {
    let searchInput = document.querySelector('input[name="search"]');
    let categorySelect = document.querySelector('select[name="category"]');
    //let status = document.querySelector('select[name="status"]');
    let equipmentID = document.querySelector('select[name="equipment_id"]');
    let stockStatus = document.querySelector('select[name="stock_status"]');
    let loader = document.querySelector('#parts-loading');
    let wrapper = document.querySelector('#parts-table-wrapper');
    let timeout = null;

    function fetchParts() {
        const search = searchInput.value;
        const category = categorySelect.value;
        const equipment_id = equipmentID.value;
        const stock_status = stockStatus.value;

        const params = new URLSearchParams();
        if (search.length >= 3 || search.length === 0) params.append('search', search);
        if (category) params.append('category', category);
        if (equipment_id) params.append('equipment_id', equipment_id);
        if (stock_status) params.append('stock_status', stock_status);  

        // Show loader
        loader.classList.remove('hidden');
        wrapper.classList.add('opacity-50', 'pointer-events-none');

        fetch("{{ route('admin.maintenance-management.parts.index') }}?" + params.toString(), {
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
        timeout = setTimeout(fetchParts, 400); // Wait 400ms before firing
    });
    categorySelect.addEventListener('change', fetchParts);
    equipmentID.addEventListener('change', fetchParts);
    stockStatus.addEventListener('change', fetchParts);
});
</script>
@endsection
