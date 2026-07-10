<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Product</th>
                <th class="py-4 px-4 text-center">Order</th>
                <th class="py-4 px-6 text-left">Customer</th>
                <th class="py-4 px-6 text-left">Delivery Address / Phone</th>
                <th class="py-4 px-4 text-center">Equipment</th>
                <th class="py-4 px-[19px] text-center">Equip. Location</th>
                <th class="py-4 px-4 text-center">Delivery | Return</th>
                <th class="py-4 px-[22px] text-center">Delivery Date</th>
                <th class="py-4 px-[22px] text-center text-blue-700">Driver / Tech</th>
                <th class="py-4 px-[22px] text-center">Return Date</th>
                <th class="py-4 px-[22px] text-center text-purple-700">Driver / Tech</th>
                <th class="py-4 px-2 text-center w-[80px]">Payment</th>
                <th class="py-4 px-1 text-center w-8"></th>
            </tr>
        </thead>
        <div id="dispatch-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                @php
                    $fullyDone = $orderProduct->delivery_status === 'Completed'
                              && $orderProduct->pickup_status === 'Completed';

                    // Delivery | Return column values
                    // Dispatch is truck-only: delivery load location = equipment's physical store
                    $deliveryPending = $orderProduct->delivery_status === 'Pending';
                    if (!$orderProduct->equipment?->current_status?->isRented()) {
                        $dispatchDeliveryStore = $orderProduct->equipment?->store?->store_name
                            ?? $orderProduct->softAssignment?->equipment?->store?->store_name
                            ?? $orderProduct->deliveryStore?->store_name;
                    } else {
                        // Equipment is rented (at customer site) — fall back to deliveryStore for history
                        $dispatchDeliveryStore = $orderProduct->deliveryStore?->store_name;
                    }
                    $dispatchReturnStore = $orderProduct->pickupStore?->store_name;

                    // Dispatch date overrides (raw ISO for data attributes + badge logic)
                    $rentalDeliveryDateRaw   = $orderProduct->delivery_date
                        ? \Carbon\Carbon::parse($orderProduct->delivery_date)->format('Y-m-d') : '';
                    $rentalReturnDateRaw     = $orderProduct->pickup_date
                        ? \Carbon\Carbon::parse($orderProduct->pickup_date)->format('Y-m-d') : '';
                    $dispatchDeliveryDateRaw = $orderProduct->dispatch_delivery_date
                        ? \Carbon\Carbon::parse($orderProduct->dispatch_delivery_date)->format('Y-m-d') : '';
                    $dispatchReturnDateRaw   = $orderProduct->dispatch_return_date
                        ? \Carbon\Carbon::parse($orderProduct->dispatch_return_date)->format('Y-m-d') : '';
                    $isEarlyDelivery   = $dispatchDeliveryDateRaw && $rentalDeliveryDateRaw
                        && $dispatchDeliveryDateRaw < $rentalDeliveryDateRaw;
                    $isLateReturnPickup = $dispatchReturnDateRaw && $rentalReturnDateRaw
                        && $dispatchReturnDateRaw > $rentalReturnDateRaw;
                @endphp
                <tr id="order-row-{{ $orderProduct->id }}"
                    class="hover:bg-gray-50 {{ $fullyDone ? 'bg-green-50/60' : '' }}">

                    {{-- Product --}}
                    <td class="py-4 px-6 text-left min-w-[210px] max-w-[210px] {{ $fullyDone ? 'opacity-60' : '' }}">
                        {{ $orderProduct->product_name }}

                        @php
                            $categories = $orderProduct->product?->categories ?? collect();
                            $count = $categories->count();
                        @endphp

                        @if ($count === 1)
                            <div class="text-xs text-gray-500 mt-1">
                                <span>{{ $categories->first()->title }}</span>
                            </div>
                        @elseif ($count > 1)
                            @php
                                $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';
                                foreach ($categories as $cat) {
                                    $tooltipHtml .= '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                                }
                            @endphp
                            <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                                data-tooltip-html="{{ $tooltipHtml }}">
                                Categories ({{ $count }})
                            </span>
                        @endif
                    </td>

                    <td class="py-4 px-4 text-center">
                        {!! $orderProduct->order?->view_link ?? '-' !!}
                    </td>

                    <td class="py-4 px-6 text-left w-[275px] max-w-[275px] overflow-hidden">
                        <div class="font-medium truncate">
                            {{ $orderProduct->order?->customer_name ?? '-' }}
                        </div>
                        @php $customer = $orderProduct->order?->customer; @endphp
                        @if ($customer && $customer->company_name)
                            <div class="text-xs text-gray-500 mt-1 truncate">
                                @if (!empty($customer->company_website))
                                    <a href="{{ $customer->company_website }}" class="underline truncate block">{{ $customer->company_name }}</a>
                                @else
                                    <span>{{ $customer->company_name }}</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    {{-- Delivery Address / Phone --}}
                    <td class="py-4 px-6 text-left whitespace-normal min-w-[320px]">
                        <div>{{ $orderProduct->order?->shippingAddress?->full_address ?? '-' }}</div>
                        @if ($orderProduct->order?->shippingAddress?->phone)
                            <div class="text-xs text-gray-500 mt-1">{{ $orderProduct->order->shippingAddress->phone }}</div>
                        @endif
                    </td>

                    <td class="py-4 px-4 text-center">
                        @php $preferredCategoryId = $orderProduct->product?->categories?->first()?->id; @endphp
                        <div class="flex flex-col items-center gap-1">
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}"
                                data-category-id="{{ $preferredCategoryId ?? '' }}"
                                data-product-name="{{ $orderProduct->product_name }}">
                                @if ($orderProduct?->equipment?->current_status?->isRented())
                                    {{ isset($orderProduct->equipment_details['equipment_name']) ? $orderProduct->equipment_details['equipment_name'] : 'Assign' }}
                                @else
                                    {{ $orderProduct->softAssignment?->equipment?->equipment_name ?: 'Assign' }}
                                @endif
                            </button>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-700 border">
                                @if ($orderProduct?->equipment?->current_status?->isRented())
                                    {{ isset($orderProduct->equipment_details['equipment_id']) ? $orderProduct->equipment_details['equipment_id'] : '-' }}
                                @else
                                    {{ $orderProduct->softAssignment?->equipment?->equipment_id ?? '-' }}
                                @endif
                            </span>
                        </div>
                    </td>

                    <td class="py-4 px-[19px] text-center">
                        <div class="inline-flex items-center gap-1">
                            @if ($orderProduct?->equipment?->current_status?->isRented())
                                <a href="{{ route('admin.crm.customers.view', $orderProduct?->order?->customer?->unique_id ?? 0) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $orderProduct?->order?->customer_name ?? '-' }}
                                </a>
                            @elseif ($orderProduct?->softAssignment?->equipment)
                                <button type="button" class="text-blue-600 underline store-assign-btn"
                                    data-equipment-unique-id="{{ $orderProduct->softAssignment->equipment->unique_id }}"
                                    data-equipment-name="{{ $orderProduct->softAssignment->equipment->equipment_name }}">
                                    {{ $orderProduct->softAssignment->equipment->store?->store_name ?? 'Assign Location' }}
                                </button>
                            @else
                                -
                            @endif
                        </div>
                    </td>

                    {{-- Delivery | Return --}}
                    <td class="py-4 px-4 text-center">
                        <div class="flex flex-col items-center gap-0.5 leading-tight">
                            @if ($deliveryPending)
                                <span class="text-gray-700">{{ $dispatchDeliveryStore ?? '-' }}</span>
                                <span class="text-xs text-gray-500">{{ $dispatchReturnStore ?? '-' }}</span>
                            @else
                                <span class="text-xs text-gray-500">{{ $dispatchDeliveryStore ?? '-' }}</span>
                                <span class="text-gray-700">{{ $dispatchReturnStore ?? '-' }}</span>
                            @endif
                        </div>
                    </td>

                    {{-- Delivery Date --}}
                    <td class="py-4 px-[22px] text-center">
                        @php
                            $iconColor = $orderProduct->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if (!empty($orderProduct->delivery_transport_mode))
                                    @if ($orderProduct->delivery_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $iconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $iconColor }}" />
                                    @endif
                                @endif
                                <span>
                                    {{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : 'N/A' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : ' ' }}
                            </span>
                            @if ($isEarlyDelivery)
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-blue-100 text-blue-700">EARLY</span>
                                    <span class="text-[10px] text-blue-600">{{ \Carbon\Carbon::parse($dispatchDeliveryDateRaw)->format('M j') }}</span>
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- Delivery Driver --}}
                    <td class="py-4 px-[22px] text-center min-w-[8rem]">
                        @if ($orderProduct->deliveryEmployee)
                            <button type="button"
                                class="assign-driver-btn text-xs font-semibold text-blue-700 hover:text-blue-900 hover:underline cursor-pointer"
                                title="Click to reassign delivery driver"
                                data-slot="delivery"
                                data-delivery-completed="{{ $orderProduct->delivery_status === 'Completed' ? '1' : '0' }}"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $orderProduct->delivery_by }}"
                                data-current-driver-name="{{ $orderProduct->deliveryEmployee->full_name }}"
                                data-rental-delivery-date="{{ $rentalDeliveryDateRaw }}"
                                data-rental-return-date="{{ $rentalReturnDateRaw }}"
                                data-dispatch-delivery-date="{{ $dispatchDeliveryDateRaw }}"
                                data-dispatch-return-date="{{ $dispatchReturnDateRaw }}"
                                data-other-slot-driver-id="{{ $orderProduct->pickup_by ?? '' }}"
                                data-other-slot-driver-name="{{ $orderProduct->pickupEmployee?->full_name ?? '' }}">
                                {{ $orderProduct->deliveryEmployee->full_name }}
                            </button>
                        @else
                            <button type="button"
                                class="assign-driver-btn inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium"
                                title="Assign delivery driver"
                                data-slot="delivery"
                                data-delivery-completed="{{ $orderProduct->delivery_status === 'Completed' ? '1' : '0' }}"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id=""
                                data-current-driver-name=""
                                data-rental-delivery-date="{{ $rentalDeliveryDateRaw }}"
                                data-rental-return-date="{{ $rentalReturnDateRaw }}"
                                data-dispatch-delivery-date="{{ $dispatchDeliveryDateRaw }}"
                                data-dispatch-return-date="{{ $dispatchReturnDateRaw }}"
                                data-other-slot-driver-id="{{ $orderProduct->pickup_by ?? '' }}"
                                data-other-slot-driver-name="{{ $orderProduct->pickupEmployee?->full_name ?? '' }}">
                                @if ($orderProduct->delivery_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3.5 h-3.5" />
                                @endif
                                Assign
                            </button>
                        @endif
                    </td>

                    {{-- Return Date --}}
                    <td class="py-4 px-[22px] text-center">
                        @php
                            $iconColor = $orderProduct->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if (!empty($orderProduct->pickup_transport_mode))
                                    @if ($orderProduct->pickup_transport_mode === 'Truck')
                                        <x-heroicon-o-truck class="w-4 h-4 {{ $iconColor }}" />
                                    @else
                                        <x-heroicon-o-building-storefront class="w-4 h-4 {{ $iconColor }}" />
                                    @endif
                                @endif
                                <span>
                                    {{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : 'N/A' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : ' ' }}
                            </span>
                            @if ($isLateReturnPickup)
                                <div class="flex items-center gap-1 mt-1">
                                    <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">LATE PICKUP</span>
                                    <span class="text-[10px] text-orange-600">{{ \Carbon\Carbon::parse($dispatchReturnDateRaw)->format('M j') }}</span>
                                </div>
                            @endif
                        </div>
                    </td>

                    {{-- Return Driver --}}
                    <td class="py-4 px-[22px] text-center min-w-[8rem]">
                        @if ($orderProduct->pickupEmployee)
                            <button type="button"
                                class="assign-driver-btn text-xs font-semibold text-purple-700 hover:text-purple-900 hover:underline cursor-pointer"
                                title="Click to reassign return driver"
                                data-slot="return"
                                data-delivery-completed="{{ $orderProduct->delivery_status === 'Completed' ? '1' : '0' }}"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $orderProduct->pickup_by }}"
                                data-current-driver-name="{{ $orderProduct->pickupEmployee->full_name }}"
                                data-rental-delivery-date="{{ $rentalDeliveryDateRaw }}"
                                data-rental-return-date="{{ $rentalReturnDateRaw }}"
                                data-dispatch-delivery-date="{{ $dispatchDeliveryDateRaw }}"
                                data-dispatch-return-date="{{ $dispatchReturnDateRaw }}"
                                data-other-slot-driver-id="{{ $orderProduct->delivery_by ?? '' }}"
                                data-other-slot-driver-name="{{ $orderProduct->deliveryEmployee?->full_name ?? '' }}">
                                {{ $orderProduct->pickupEmployee->full_name }}
                            </button>
                        @else
                            <button type="button"
                                class="assign-driver-btn inline-flex items-center gap-1 text-xs text-purple-600 hover:text-purple-800 font-medium"
                                title="Assign return driver"
                                data-slot="return"
                                data-delivery-completed="{{ $orderProduct->delivery_status === 'Completed' ? '1' : '0' }}"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id=""
                                data-current-driver-name=""
                                data-rental-delivery-date="{{ $rentalDeliveryDateRaw }}"
                                data-rental-return-date="{{ $rentalReturnDateRaw }}"
                                data-dispatch-delivery-date="{{ $dispatchDeliveryDateRaw }}"
                                data-dispatch-return-date="{{ $dispatchReturnDateRaw }}"
                                data-other-slot-driver-id="{{ $orderProduct->delivery_by ?? '' }}"
                                data-other-slot-driver-name="{{ $orderProduct->deliveryEmployee?->full_name ?? '' }}">
                                @if ($orderProduct->pickup_transport_mode === 'Truck')
                                    <x-heroicon-o-truck class="w-3.5 h-3.5" />
                                @else
                                    <x-heroicon-o-building-storefront class="w-3.5 h-3.5" />
                                @endif
                                Assign
                            </button>
                        @endif
                    </td>

                    <td class="py-4 px-2 text-center w-[80px]">
                        {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order?->last_payment_status) !!}
                    </td>

                    <td class="py-4 px-1 text-center w-8">
                        <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order?->unique_id ?? 0) }}"
                            class="text-sky-600 hover:text-sky-800" title="View Order">
                            @if ($orderProduct?->order?->notes->isNotEmpty())
                                <x-heroicon-o-book-open class="w-4 h-4" />
                            @else
                                <x-heroicon-o-eye class="w-4 h-4" />
                            @endif
                        </a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center text-sm text-gray-500 px-4 py-6">
                        @if ($orderProducts)
                            No dispatch records found.
                        @else
                            <span class="text-gray-400 italic">Loading dispatch data…</span>
                        @endif
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($orderProducts)
    <div class="mt-6">
        {{ $orderProducts->links('vendor.pagination.tailwind') }}
    </div>
@endif
