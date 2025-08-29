{{-- Equipment Table --}}
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full whitespace-nowrap" id="equipment-table-wrapper">
            {{-- Table Header --}}
            @include('admin.maintenance_management.equipment.partials._table-header')
            <div id="equipment-loading" class="hidden"></div>
            <tbody class="divide-y divide-gray-200">
                @forelse($equipment as $item)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="py-4 px-6">
                            <span class="text-sm font-medium text-gray-900">{{ $item->product_category->title ?? '-' }}</span>
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
                            {!! \App\Helpers\CustomHelper::statusBadge($item->current_status->label()) !!}
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-900">-</span>
                        </td>
                        <td class="py-4 px-6">
                            <span class="text-sm text-gray-900">-</span>
                        </td>
                        <td class="py-3 px-3">
                            -
                        </td>
                        <td class="py-4 px-3">
                            -
                        </td>
                        <td class="py-4 px-3">
                            -
                        </td>
                        <td class="px-3 py-3 whitespace-nowrap text-center">
                            -
                        </td>
                        <td class="py-3 px-3">
                            <div class="flex items-center space-x-1">
                                {{-- Edit Button --}}
                                <a href="{{ route('admin.maintenance-management.equipment.edit', $item->unique_id) }}"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300"
                                    title="Edit">
                                    <x-heroicon-o-pencil class="w-5 h-5" />
                                </a>

                                {{-- Delete Button --}}
                                <form action="{{ route('admin.maintenance-management.equipment.delete', $item->unique_id) }}"
                                    method="POST" class="inline"
                                    onsubmit="return confirm('Are you sure you want to delete this equipment?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                        title="Delete">
                                        <x-heroicon-o-trash class="w-5 h-5" />
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
                            <a href="{{ route('admin.maintenance-management.equipment.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors">
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
    @if($equipment->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $equipment->appends(request()->query())->links() }}
        </div>
    @endif
</div>
