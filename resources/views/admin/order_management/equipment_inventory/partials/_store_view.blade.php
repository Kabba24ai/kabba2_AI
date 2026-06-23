<div class="flex gap-4 overflow-x-auto pb-2">
    @forelse ($stores as $store)
        @php $storeEquipment = $allEquipment->where('store_id', $store->id)->values(); @endphp
        <div class="flex-1 min-w-[300px] bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="bg-gray-100 px-4 py-3 border-b border-gray-200 flex items-center justify-between">
                <h3 class="font-semibold text-gray-800">{{ $store->store_name }}</h3>
                <span class="text-xs text-gray-500 bg-white px-2 py-0.5 rounded-full border border-gray-200">
                    {{ $storeEquipment->count() }} items
                </span>
            </div>
            <div class="overflow-y-auto" style="max-height: calc(90vh - 260px)">
                <table class="w-full text-sm divide-y divide-gray-100">
                    <thead class="bg-gray-50 text-gray-600 sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 text-left font-semibold whitespace-nowrap">Category</th>
                            <th class="px-3 py-2 text-left font-semibold whitespace-nowrap">Equipment Name</th>
                            <th class="px-3 py-2 text-left font-semibold whitespace-nowrap">Equip. ID</th>
                            <th class="px-3 py-2 text-center font-semibold">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($storeEquipment as $eq)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2.5 text-gray-700 text-xs">
                                    {{ $eq->category_name ?: '-' }}
                                </td>
                                <td class="px-3 py-2.5 break-words max-w-[160px] text-xs">
                                    {{ $eq->equipment_name }}
                                </td>
                                <td class="px-3 py-2.5">
                                    <a href="{{ route('admin.maintenance-management.equipment.edit', $eq->unique_id) }}"
                                       class="text-blue-600 uppercase text-xs font-medium hover:underline">
                                        {{ $eq->equipment_id }}
                                    </a>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    @switch($eq->status_label)
                                        @case('Damaged')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-red-600 inline-block" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Damaged">
                                                <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                                <path d="M12 9v4"/>
                                                <path d="M12 17h.01"/>
                                            </svg>
                                        @break
                                        @case('Maint. Hold')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-yellow-600 inline-block" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Maint. Hold">
                                                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z"/>
                                            </svg>
                                        @break
                                        @case('Available')
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500 inline-block" fill="none"
                                                viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" title="Available">
                                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                                <path d="m9 11 3 3L22 4"/>
                                            </svg>
                                        @break
                                        @default
                                            <span class="text-xs text-gray-400" title="{{ $eq->status_label }}">?</span>
                                    @endswitch
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-3 py-6 text-center text-gray-400 text-sm italic">
                                    No equipment
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <div class="w-full text-center py-12 text-gray-400 italic">
            No store equipment found matching the current filters.
        </div>
    @endforelse
</div>
