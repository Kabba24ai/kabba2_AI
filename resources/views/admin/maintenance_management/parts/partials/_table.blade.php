 {{-- Parts Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    @if($parts->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full" id="parts-table-wrapper">
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
                <div id="parts-loading" class="hidden"></div>
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
                                    <a href="{{ route('admin.maintenance-management.parts.edit', $part->id) }}" 
                                        class="p-1 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 rounded-lg transition-colors" 
                                        title="Edit Part">
                                        <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                    <form action="{{ route('admin.maintenance-management.parts.delete', $part->id) }}" method="POST" class="inline">
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