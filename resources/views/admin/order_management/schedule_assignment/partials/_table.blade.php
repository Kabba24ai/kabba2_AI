<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm ">
    <thead class="bg-gray-100 text-gray-600 sticky top-0 z-10">
        <tr>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Category</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment Name</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equip. ID</th>
            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Status</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>

            <!-- Calendar headers -->
            @foreach ($dates as $date)
                <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">
                    <div class="flex flex-col items-center">
                        <span class="text-xs text-gray-500">{{ $date->format('D') }}</span>
                        {{ $date->format('M d') }}
                    </div>
                </th>
            @endforeach
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 text-gray-900 whitespace-nowrap">
        @forelse($equipment as $eq)
            @php
                $overdueOrders = $eq->overdueOrderProducts;
                $hasOverdueOrders = $overdueOrders->isNotEmpty();
                $activeOrderProduct = $eq->lastOrderProduct;
                $allSoftAssignments = $eq->softAssignments;
                $today = today()->toDateString();
            @endphp
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
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 {{ $hasOverdueOrders ? 'text-red-600' : 'text-blue-600' }}" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path d="M16 21v-2a4 4 0 0 0-8 0v2" />
                                    <circle cx="12" cy="7" r="4" />
                                </svg>
                                <span
                                    class="px-2 py-1 rounded-full text-xs font-medium uppercase {{ $hasOverdueOrders ? 'bg-red-600 text-white' : 'bg-blue-100 text-blue-700' }}">{{ $eq->status_label }}</span>
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
                        @if ($eq->status_label == 'Rented')
                            <a href="{{ $eq->order?->customer ? route('admin.crm.customers.view', $eq->order->customer->unique_id) : '#' }}"
                                 class="text-blue-600 hover:underline">
                                {{ $eq->order?->customer_name ?? '-' }}
                            </a>
                        @else
                            @if ($eq->store?->store_name)
                                <button type="button" class="text-blue-600 underline store-assign-btn"
                                    data-equipment-unique-id="{{ $eq->unique_id }}"
                                    data-equipment-name="{{ $eq->equipment_name }}">
                                    {{ $eq->store->store_name }}
                                </button>
                            @else
                                <button type="button" class="text-blue-600 underline store-assign-btn"
                                    data-equipment-unique-id="{{ $eq->unique_id }}"
                                    data-equipment-name="{{ $eq->equipment_name }}">
                                    Assign Location
                                </button>
                            @endif
                        @endif
                    </div>
                </td>
                @foreach ($dates as $date)
                    <td class="px-4 py-4 text-center">
                        @php
                            $day = $date->format('Y-m-d');

                            // Uses eager-loaded lastOrderProduct instead of querying per cell.
                            $isBooked = $activeOrderProduct
                                && $activeOrderProduct->delivery_date
                                && $activeOrderProduct->pickup_date
                                && $day >= $activeOrderProduct->delivery_date
                                && $day <= $activeOrderProduct->pickup_date;

                            $visibleOverdueOrders = $overdueOrders->filter(function ($overdueOrder) use ($day, $today) {
                                if (!$overdueOrder?->pickup_date) {
                                    return false;
                                }

                                $pickupDay = \Illuminate\Support\Carbon::parse($overdueOrder->pickup_date)->toDateString();
                                return $day >= $pickupDay && $day <= $today;
                            });

                            $softAssignments = $allSoftAssignments->filter(function ($assignment) use ($day) {
                                $orderProduct = $assignment->orderProduct;

                                if (!$orderProduct?->delivery_date || !$orderProduct?->pickup_date) {
                                    return false;
                                }

                                return $day >= $orderProduct->delivery_date
                                    && $day <= $orderProduct->pickup_date;
                            });

                            $isSoftAssigned = $softAssignments->isNotEmpty();

                            $color = match ($eq->status_label) {
                                'Available' => 'green-100',
                                'Maint. Hold' => 'yellow-100',
                                'Damaged' => 'red-100',
                                default => 'green-100',
                            };

                            $textColor = 'text-gray-600';
                            // Light blue for soft assign, dark blue with white text for hard assign
                            $isReturnDay = $day == $activeOrderProduct?->pickup_date;
                            if ($isBooked && !$isReturnDay) {
                                $color = 'blue-600';
                                $textColor = 'text-white';
                            }

                            $hasVisibleSoftAssignments = $softAssignments->filter(function($assignment) {
                                return in_array($assignment->orderProduct?->delivery_status, ['Pending'], true)
                                    || in_array($assignment->orderProduct?->pickup_status, ['Pending'], true);
                            })->isNotEmpty();

                            $hasAny = $isBooked || $hasVisibleSoftAssignments || $visibleOverdueOrders->isNotEmpty();

                        @endphp

                        @if ($hasAny)
                            <div count="{{ count($softAssignments) }}" flag="{{ $isBooked && $isSoftAssigned }}"
                                class="w-auto rounded text-xs flex flex-col items-center justify-center group relative overflow-hidden rounded">

                                @foreach ($visibleOverdueOrders as $overdueOrder)
                                    <div class="px-2 font-bold group relative text-white bg-red-600 rounded w-full text-center"
                                        title="{{ $overdueOrder->order?->customer_name }}">
                                        @if ($isReturnDay)
                                            <span
                                                class="absolute inset-y-0 left-0 w-[15%] bg-blue-600 rounded-l"></span>
                                        @endif
                                        <button type="button"
                                            class="underline equipment-assign-btn @if($overdueOrder?->order?->last_payment_type == \App\Enums\Orders\OrderPaymentMethod::COD) text-yellow-500 @else text-white @endif"
                                            data-order-product-unique-id="{{ $overdueOrder->unique_id }}"
                                            data-product-name="{{ $overdueOrder->product_name }}"
                                            data-order-id="{{ $overdueOrder->order?->order_number }}"
                                            data-order-unique-id="{{ $overdueOrder->order?->unique_id }}"
                                            data-category-id=""
                                            data-customer-name="{{ $overdueOrder->order?->customer_name }}"
                                            data-delivery-transport-mode="{{ $overdueOrder->delivery_transport_mode ?? '' }}"
                                            data-pickup-transport-mode="{{ $overdueOrder->pickup_transport_mode ?? '' }}"
                                            data-is-hard-assigned="true"
                                            data-assigned-equipment-name="{{ $eq->equipment_name }}"
                                            data-assigned-equipment-id="{{ $eq->equipment_id }}">
                                            {{ $overdueOrder->order?->order_number ?? '-' }}
                                        </button>
                                    </div>
                                @endforeach

                                @if ($isBooked)
                                    <div class="px-2 font-bold group relative {{ $textColor }} bg-{{ $color }} rounded w-full text-center"
                                        title="{{ $activeOrderProduct?->order?->customer_name }}">
                                        @if ($isReturnDay)
                                            <span
                                                class="absolute inset-y-0 left-0 w-[15%] bg-blue-600 rounded-l"></span>
                                        @endif
                                        <button type="button"
                                            class="underline equipment-assign-btn @if($activeOrderProduct?->order?->last_payment_type == \App\Enums\Orders\OrderPaymentMethod::COD) text-yellow-500 @else {{ $textColor }} @endif"
                                            data-order-product-unique-id="{{ $activeOrderProduct?->unique_id }}"
                                            data-product-name="{{ $activeOrderProduct?->product_name }}"
                                            data-order-id="{{ $activeOrderProduct?->order?->order_number }}"
                                            data-order-unique-id="{{ $activeOrderProduct?->order?->unique_id }}"
                                            data-category-id=""
                                            data-customer-name="{{ $activeOrderProduct?->order?->customer_name }}"
                                            data-delivery-transport-mode="{{ $activeOrderProduct?->delivery_transport_mode ?? '' }}"
                                            data-pickup-transport-mode="{{ $activeOrderProduct?->pickup_transport_mode ?? '' }}"
                                            data-is-hard-assigned="true"
                                            data-assigned-equipment-name="{{ $eq->equipment_name }}"
                                            data-assigned-equipment-id="{{ $eq->equipment_id }}">
                                            {{ $activeOrderProduct?->order?->order_number ?? '-' }}
                                        </button>
                                    </div>
                                @endif

                                <div
                                    class="text-gray-600 bg-blue-100 rounded mt-1 w-full flex flex-col items-center justify-center">
                                    @foreach ($softAssignments as $assignment)
                                        <div class="group relative"
                                            title="{{ $assignment->orderProduct?->order?->customer_name }}">
                                            @if (
                                                in_array($assignment->orderProduct?->delivery_status, ['Pending'], true)
                                                || in_array($assignment->orderProduct?->pickup_status, ['Pending'], true)
                                            )
                                                <button type="button" class="text-xs underline px-2 equipment-assign-btn @if($assignment->order?->last_payment_type == \App\Enums\Orders\OrderPaymentMethod::COD) text-yellow-500 @endif"
                                                    data-order-product-unique-id="{{ $assignment->orderProduct->unique_id }}"
                                                    data-product-name="{{ $assignment->orderProduct->product_name }}"
                                                    data-order-unique-id="{{ $assignment->orderProduct?->order?->unique_id }}"
                                                    data-order-id="{{ $assignment->orderProduct?->order?->order_number }}"
                                                    data-category-id="{{ $assignment->orderProduct?->product?->categories?->first()?->id ?? '' }}"
                                                    data-customer-name="{{ $assignment->orderProduct?->order?->customer_name }}"
                                                    data-delivery-transport-mode="{{ $assignment->orderProduct->delivery_transport_mode ?? '' }}"
                                                    data-pickup-transport-mode="{{ $assignment->orderProduct->pickup_transport_mode ?? '' }}">
                                                    {{ $assignment->order?->order_number ?? '-' }}
                                                </button>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div
                                class="w-auto h-4 bg-{{ $color }} rounded text-xs flex items-center justify-center text-gray-500">
                            </div>
                        @endif

                    </td>
                @endforeach

            </tr>
            @empty
                <tr>
                    <td colspan="{{ 5 + count($dates) }}" class="px-4 py-4 text-center text-gray-500">
                        @if ($equipment)
                            No equipment found.
                        @else
                            <span class="text-gray-400 italic">inhale… exhale… bringing your data to life…</span>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- Pagination --}}
    @if ($equipment)
        {{-- <div class="mt-6">
            {{ $equipment->links('vendor.pagination.tailwind') }}
        </div> --}}
    @endif
