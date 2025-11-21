<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm ">
    <thead class="bg-gray-100 text-gray-600 ">
        <tr>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment Name</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equip. ID</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Status</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>

            <!-- Calendar headers -->
            @foreach ($dates as $date)
                <th class="px-4 py-3 text-left font-semibold">{{ $date->format('Md') }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 text-gray-900 whitespace-nowrap">
        @forelse($equipment as $eq)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-4 font-semibold  text-gray-700">
                    @if ($eq->category_name)
                        {{ $eq->category_name }}
                    @else
                        <span class="text-gray-400 italic">None</span>
                    @endif
                </td>
                <td class="px-4 py-4 break-words">{{ $eq->equipment_name }}</td>
                <td class="px-4 py-4">
                    <a href="{{ route('admin.maintenance-management.equipment.edit', $eq->unique_id) }}"
                        class="text-blue-600 uppercase">{{ $eq->equipment_id }}</a>
                </td>
                <td class="px-4 py-4">
                    <div class="inline-flex items-center gap-1.5 whitespace-nowrap">
                        @switch($eq->status_label)
                            @case('Damaged')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-red-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z" />
                                    <path d="M12 9v4" />
                                    <path d="M12 17h.01" />
                                </svg>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700 uppercase">{{ $eq->status_label }}
                                </span>
                            @break

                            @case('Maint. Hold')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-yellow-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path
                                        d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 1 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94Z" />
                                </svg>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 uppercase">{{ $eq->status_label }}</span>
                            @break

                            @case('Rented')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-blue-600" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-700 uppercase">{{ $eq->status_label }}</span>
                            @break

                            @case('Available')
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-green-500" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                                    <path d="m9 11 3 3L22 4" />
                                </svg>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700 uppercase">{{ $eq->status_label }}</span>
                            @break

                            @default
                                <span class="px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-700 uppercase">
                                    {{ $eq->status_label ?? 'Unknown' }}
                                </span>
                        @endswitch
                    </div>
                </td>
                <td class="px-4 py-4">
                    <div class="inline-flex items-center gap-1">
                        @if ($eq->status_label == 'Rented' && $eq?->orderProduct?->checklistQuestions->isNotEmpty())
                            <a href="{{ route('admin.crm.customers.view', $eq->order->customer->unique_id) }}"
                                target="_blank" class="text-blue-600 hover:underline">
                                {{ $eq->order->customer_name ?? '-' }}
                            </a>
                        @else
                            @if ($eq->store?->store_name)
                                {{ $eq->store->store_name }}
                            @else
                                -
                            @endif
                        @endif
                    </div>
                </td>
                @php
                    $flag = false;
                @endphp
                @foreach ($dates as $date)
                    <td class="px-4 py-4 text-center">
                        @php
                            $isBooked = $eq
                                ->lastOrderProduct()
                                ->whereDate('delivery_date', $date->format('Y-m-d'))
                                ->exists();
                            $color = match ($eq->status_label) {
                                'Available' => 'green',
                                'Maint. Hold' => 'yellow',
                                'Damaged' => 'red',
                                default => 'blue',
                            };
                            if ($isBooked) {
                                $flag = true;
                            }
                        @endphp
                        @if ($isBooked)
                            <div
                                class="w-auto h-4 bg-blue-100 rounded text-xs flex items-center justify-center text-gray-500">
                                {{ $eq?->order?->order_number ?? '' }}
                            </div>
                        @else
                            <div
                                class="w-auto h-4 bg-{{ $flag ? 'blue' : $color }}-100 rounded text-xs flex items-center justify-center text-gray-500">
                            </div>
                        @endif
                    </td>
                @endforeach

            </tr>
            @empty
                <tr>
                    <td colspan="{{ 7 + count($dates) }}" class="px-4 py-4 text-center text-gray-500">
                        No equipment found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $equipment->links('vendor.pagination.tailwind') }}
    </div>
