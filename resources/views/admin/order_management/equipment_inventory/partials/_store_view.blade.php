{{-- ── Store panels: equipment physically at each store ─────────────────────── --}}
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
        <div class="w-full text-center py-8 text-gray-400 italic text-sm">
            No store equipment found matching the current filters.
        </div>
    @endforelse
</div>

{{-- ── Rented Equipment table: out with customers ───────────────────────────── --}}
@if ($rentedEquipment->isNotEmpty())
<div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
    <div class="bg-blue-50 px-4 py-3 border-b border-blue-200 flex items-center gap-2">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-blue-600" fill="none"
            viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path d="M16 21v-2a4 4 0 0 0-8 0v2"/>
            <circle cx="12" cy="7" r="4"/>
        </svg>
        <h3 class="font-semibold text-blue-800">Rented Equipment</h3>
        <span class="text-xs text-blue-500 bg-white px-2 py-0.5 rounded-full border border-blue-200 ml-1">
            {{ $rentedEquipment->count() }} items
        </span>
    </div>
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm divide-y divide-gray-200">
            <thead class="bg-gray-50 text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment Name</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equip. ID</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Status</th>
                    <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Customer / Location</th>
                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Last Order</th>
                    <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Next Order</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 text-gray-900">
                @foreach ($rentedEquipment as $eq)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-700 text-sm">
                            {{ $eq->category_name ?: '-' }}
                        </td>
                        <td class="px-4 py-3 break-words text-sm">{{ $eq->equipment_name }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.maintenance-management.equipment.edit', $eq->unique_id) }}"
                               class="text-blue-600 uppercase text-sm hover:underline">{{ $eq->equipment_id }}</a>
                        </td>
                        <td class="px-4 py-3">
                            <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 uppercase">
                                    Rented
                                </span>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($eq->order?->customer)
                                <a href="{{ route('admin.crm.customers.view', $eq->order->customer->unique_id) }}"
                                   class="text-blue-600 hover:underline text-sm">
                                    {{ $eq->order->customer_name ?? '-' }}
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($eq->lastCompletedOrderProduct?->order)
                                <a href="{{ route('admin.order-management.orders.edit', $eq->lastCompletedOrderProduct->order->unique_id) }}"
                                   class="text-blue-600 hover:underline font-medium text-sm">
                                    {{ $eq->lastCompletedOrderProduct->order->order_number }}
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($eq->nextInventoryOrder)
                                <a href="{{ route('admin.order-management.orders.edit', $eq->nextInventoryOrder->unique_id) }}"
                                   class="text-orange-600 hover:underline font-medium text-sm">
                                    {{ $eq->nextInventoryOrder->order_number }}
                                </a>
                            @else
                                <span class="text-gray-400 text-sm">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
