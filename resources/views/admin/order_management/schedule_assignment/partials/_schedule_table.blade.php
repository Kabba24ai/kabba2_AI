<div id="schedule-loading" class="hidden"></div>
<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm ">
    <thead class="bg-gray-100 text-gray-600 sticky top-0 z-10">
        <tr>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Product</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Order</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Customer</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Address</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Phone</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Equipment</th>
            <th class="px-4 py-3 text-center font-semibold whitespace-nowrap">Equipment ID</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Location</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Delivery Date</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Return Date</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Payment</th>
            <th class="px-4 py-3 text-left font-semibold whitespace-nowrap">Actions</th>
        </tr>
    </thead>
    <tbody class="divide-y divide-gray-100 text-gray-900 whitespace-nowrap">
        @forelse ($orderProducts as $orderProduct)
            <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
                <td class="whitespace-nowrap px-4 py-3 text-left min-w-4xs max-w-4xs">
                    {{ $orderProduct->product_name }}

                    @php
                        $categories = $orderProduct->product?->categories ?? collect();
                        $count = $categories->count();
                    @endphp

                    @if ($count === 1)
                        <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                            <span>{{ $categories->first()->title }}</span>
                        </div>
                    @elseif ($count > 1)
                        @php
                            $tooltipHtml = '<div class="font-semibold mb-2">Categories:</div>';

                            foreach ($categories as $cat) {
                                $tooltipHtml .=
                                    '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                            }
                        @endphp
                        <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                            data-tooltip-html="{{ $tooltipHtml }}">
                            Categories ({{ $count }})
                        </span>
                    @endif
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-center">
                    {!! $orderProduct->order?->view_link ?? '-' !!}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-left">

                    <div class="font-medium">
                        {{ $orderProduct->order?->customer_name ?? '-' }}
                    </div>

                    @php
                        $customer = $orderProduct->order?->customer;
                    @endphp

                    @if ($customer && $customer->company_name)
                        <div class="text-xs text-gray-500 mt-1 ">

                            @if (!empty($customer->company_website))
                                <!-- With website: underline + clickable -->
                                <a href="{{ $customer->company_website }}"  class="underline ">
                                    {{ $customer->company_name }}
                                </a>
                            @else
                                <!-- No website: same style but not clickable -->
                                <span class="">
                                    {{ $customer->company_name }}
                                </span>
                            @endif

                        </div>
                    @endif

                </td>

                <td class="whitespace-nowrap px-4 py-3 truncate min-w-xs max-w-xs">
                    {{ $orderProduct->order?->shippingAddress?->full_address ?? '-' }}</td>
                <td class="whitespace-nowrap px-4 py-3 text-left ">{{ $orderProduct->order?->shippingAddress?->phone ?? '-' }}
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @php
                        $preferredCategoryId = $orderProduct->product?->categories?->first()?->id;
                    @endphp
                    @if ($orderProduct?->equipment?->current_status?->isRented())
                        <div class="inline-flex items-center gap-2">
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-category-id="{{ $preferredCategoryId ?? '' }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}">
                                {{ $orderProduct->equipment_details['equipment_name'] ?? 'Assign' }}
                            </button>
                            <button
                                type="button"
                                class="text-green-600 hover:text-green-800 auto-assign-direct-btn"
                                data-order-product-id="{{ $orderProduct->id }}"
                                data-row-id="order-row-{{ $orderProduct->id }}"
                                title="Auto Assign Direct"
                            >
                                <x-heroicon-o-bolt class="w-4 h-4" />
                            </button>
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2">
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-category-id="{{ $preferredCategoryId ?? '' }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}">
                                {{ $orderProduct->softAssignment?->equipment?->equipment_name ?: 'Assign' }}
                            </button>
                            <button
                                type="button"
                                class="text-green-600 hover:text-green-800 auto-assign-direct-btn"
                                data-order-product-id="{{ $orderProduct->id }}"
                                data-row-id="order-row-{{ $orderProduct->id }}"
                                title="Auto Assign Direct"
                            >
                                <x-heroicon-o-bolt class="w-4 h-4" />
                            </button>
                        </div>
                    @endif
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center ">
                    @if ($orderProduct?->equipment?->current_status?->isRented())
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $orderProduct->equipment_details['equipment_id'] }}
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            {{ $orderProduct->softAssignment?->equipment?->equipment_id ?? '-' }}
                        </span>
                    @endif
                </td>

                <td class="px-4 py-3 text-center">
                    <div class="inline-flex items-center gap-1">
                        @if ($orderProduct?->equipment?->current_status?->isRented())
                            <a href="{{ route('admin.crm.customers.view', $orderProduct?->order?->customer->unique_id) }}"
                                 class="text-blue-600 hover:underline">
                                {{ $orderProduct?->order->customer_name ?? '-' }}
                            </a>
                        @else
                            @if ($orderProduct?->softAssignment?->equipment?->store?->store_name)
                                {{ $orderProduct?->softAssignment?->equipment?->store->store_name }}
                            @else
                                -
                            @endif
                        @endif
                    </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @php
                        $iconColor =
                            $orderProduct->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
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
                    </div>
                </td>
                <td class="whitespace-nowrap px-4 py-3 text-center">
                    @php
                        $iconColor =
                            $orderProduct->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
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
                    </div>
                </td>

                <td class="whitespace-nowrap px-4 py-3 text-center">
                    {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order?->last_payment_status) !!}
                </td>
                <td class="whitespace-nowrap px-4 py-3">
                    <div class="flex gap-2 items-center justify-center">
                        <a href="{{ $orderProduct->order?->unique_id ? route('admin.order-management.orders.edit', $orderProduct->order->unique_id) : '#' }}"
                            class="text-sky-600 hover:text-sky-800" title="View" >
                            <x-heroicon-o-eye class="w-4 h-4" />
                        </a>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="12" class="text-center text-sm text-gray-500 px-4 py-6">
                    No order found.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
