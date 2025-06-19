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

        {{-- Parts Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @if($parts->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Part Name</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Category</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Equipment Name</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Equipment ID</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Part Number</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Supplier</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Unit Cost</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Stock</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($parts as $part)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-6">
                                        <span class="text-sm font-medium text-gray-900">{{ $part->part_name }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm text-gray-900">{{ $part->category }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm text-gray-900">{{ $part->equipment_name }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                            {{ $part->equipment_id }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm font-mono text-gray-900">{{ $part->part_number }}</span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm text-gray-900">{{ $part->supplier }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="text-sm font-medium text-green-600">${{ number_format($part->unit_cost, 2) }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        @php
                                            $stockBadge = match($part->stock_status) {
                                                'dni' => ['text' => 'DNI', 'class' => 'text-gray-700 bg-gray-100 border-gray-200'],
                                                'out-of-stock' => ['text' => 'Out of Stock', 'class' => 'text-red-700 bg-red-100 border-red-200'],
                                                'buy-now' => ['text' => 'Buy Now', 'class' => 'text-yellow-700 bg-yellow-100 border-yellow-200'],
                                                'in-stock' => ['text' => 'In Stock', 'class' => 'text-green-700 bg-green-100 border-green-200']
                                            };
                                        @endphp
                                        
                                        @if($part->dni)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border {{ $stockBadge['class'] }}">
                                                {{ $stockBadge['text'] }}
                                            </span>
                                        @else
                                            <button onclick="updateStock({{ $part->id }}, '{{ $part->part_name }}', {{ $part->stock_level }}, {{ $part->min_stock }})"
                                                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border cursor-pointer hover:opacity-80 transition-opacity {{ $stockBadge['class'] }}"
                                                    title="Click to update stock level">
                                                {{ $stockBadge['text'] }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center space-x-1">
                                            <a href="{{ route('admin.maintenance-management.parts.edit', $part->unique_id) }}" 
                                               class="p-1 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 rounded-lg transition-colors" 
                                               title="Edit Part">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.maintenance-management.parts.delete', $part->unique_id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete {{ $part->part_name }}?')"
                                                        class="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" 
                                                        title="Delete Part">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No parts found</h3>
                    <p class="text-gray-600 mb-4">Try adjusting your search criteria or add new parts.</p>
                    <a href="{{ route('admin.maintenance-management.parts.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Add Part</span>
                    </a>
                </div>
            @endif
        </div>
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
</script>
@endsection
