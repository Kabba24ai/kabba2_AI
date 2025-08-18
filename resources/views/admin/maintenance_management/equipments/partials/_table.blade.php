{{-- Equipment Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full" id="equipment-table-wrapper">
            {{-- Table Header --}}
            @include('admin.maintenance_management.equipments.partials._table-header')
            <div id="equipment-loading" class="hidden"></div>
            <tbody class="divide-y divide-gray-200">
                @forelse($equipments as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6">
                            <span class="text-sm font-medium text-gray-900">{{ $item->category }}</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-900">{{ $item->equipment_name }}</span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ $item->equipment_id }}
                            </span>
                        </td>
                        
                        <td class="py-3 px-3">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium border {{ $item->status_color }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-900">{{ $item->tech_manager ?? '-' }}</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-900">{{ $item->location ?? '-' }}</span>
                        </td>
                        <td class="py-3 px-3">
                            <span class="text-xs text-gray-600">
                                  {{ $item->created_at ? $item->created_at->format('M d - g:i A') : 'N/A' }}

                        </td>
                        <td class="py-4 px-3">
                            <span class="text-sm text-gray-600" title="{{ $item->rental_ready_checklist ?? 'No checklist assigned' }}">
                                {{ $item->truncated_rental_ready }}
                            </span>
                        </td>
                        <td class="py-4 px-3">
                            <span class="text-sm text-gray-600" title="{{ $item->equipment_service_list ?? 'No service list assigned' }}">
                                {{ $item->truncated_service_list }}
                            </span>
                        </td>
                        <td class="py-3 px-3">
                            @php $serviceStatus = $item->service_status; @endphp
                            <div class="flex items-center space-x-1">
                                @if($serviceStatus['status'] === 'overdue')
                                    <svg class="h-3 w-3 {{ $serviceStatus['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                    </svg>
                                @elseif($serviceStatus['status'] === 'due-soon')
                                    <svg class="h-3 w-3 {{ $serviceStatus['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @elseif($serviceStatus['status'] === 'good')
                                    <svg class="h-3 w-3 {{ $serviceStatus['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                @else
                                    <svg class="h-3 w-3 {{ $serviceStatus['color'] }}" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                    </svg>
                                @endif
                                <span class="text-xs font-medium {{ $serviceStatus['color'] }}">
                                    {{ ucfirst($serviceStatus['status']) }}
                                </span>
                            </div>
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex items-center space-x-1">
                                <a href="{{ route('admin.maintenance-management.equipments.edit', $item->unique_id) }}" 
                                   class="p-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded-lg transition-colors" 
                                   title="Edit Equipment">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                <form action="{{ route('admin.maintenance-management.equipments.delete', $item->unique_id) }}" method="POST" class="inline" 
                                      onsubmit="return confirm('Are you sure you want to delete {{ $item->equipment_name }}? This action cannot be undone.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" 
                                            title="Delete Equipment">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-12">
                            <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No equipment found</h3>
                            <p class="text-gray-600 mb-4">Try adjusting your search criteria or add new equipment.</p>
                            <a href="{{ route('admin.maintenance-management.equipments.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                </svg>
                                <span>Add Equipment</span>
                            </a>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if($equipments->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $equipments->appends(request()->query())->links() }}
        </div>
    @endif
</div>
