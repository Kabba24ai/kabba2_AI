<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Product</th>
                <th class="py-4 px-6 text-center">Order</th>
                <th class="py-4 px-6 text-left">Customer</th>
                <th class="py-4 px-6 text-left">Delivery Address</th>
                <th class="py-4 px-6 text-left">Phone</th>
                <th class="py-4 px-6 text-center">Equipment</th>
                <th class="py-4 px-6 text-center">Equipment Id</th>
                <th class="py-4 px-6 text-center">Location</th>
                <th class="py-4 px-6 text-center">Delivery Date</th>
                <th class="py-4 px-6 text-center text-blue-700">Driver</th>
                <th class="py-4 px-6 text-center">Return Date</th>
                <th class="py-4 px-6 text-center text-purple-700">Driver</th>
                <th class="py-4 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <div id="dispatch-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                @php
                    $fullyDone = $orderProduct->delivery_status === 'Completed'
                              && $orderProduct->pickup_status === 'Completed';
                @endphp
                <tr id="order-row-{{ $orderProduct->id }}"
                    class="hover:bg-gray-50 {{ $fullyDone ? 'bg-green-50/60' : '' }}">

                    {{-- Product --}}
                    <td class="py-4 px-6 text-left min-w-[9.5rem] max-w-[9.5rem] {{ $fullyDone ? 'opacity-60' : '' }}">
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

                    <td class="py-4 px-6 text-center">
                        {!! $orderProduct->order?->view_link ?? '-' !!}
                    </td>

                    <td class="py-4 px-6 text-left">
                        <div class="font-medium">
                            {{ $orderProduct->order?->customer_name ?? '-' }}
                        </div>
                        @php $customer = $orderProduct->order?->customer; @endphp
                        @if ($customer && $customer->company_name)
                            <div class="text-xs text-gray-500 mt-1">
                                @if (!empty($customer->company_website))
                                    <a href="{{ $customer->company_website }}" class="underline">{{ $customer->company_name }}</a>
                                @else
                                    <span>{{ $customer->company_name }}</span>
                                @endif
                            </div>
                        @endif
                    </td>

                    <td class="py-4 px-6 truncate min-w-xs max-w-xs">
                        {{ $orderProduct->order?->shippingAddress?->full_address ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-left">
                        {{ $orderProduct->order?->shippingAddress?->phone ?? '-' }}
                    </td>

                    <td class="py-4 px-6 text-center">
                        @php $preferredCategoryId = $orderProduct->product?->categories?->first()?->id; @endphp
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
                    </td>

                    <td class="py-4 px-6 text-center">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                            @if ($orderProduct?->equipment?->current_status?->isRented())
                                {{ isset($orderProduct->equipment_details['equipment_id']) ? $orderProduct->equipment_details['equipment_id'] : '-' }}
                            @else
                                {{ $orderProduct->softAssignment?->equipment?->equipment_id ?? '-' }}
                            @endif
                        </span>
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="inline-flex items-center gap-1">
                            @if ($orderProduct?->equipment?->current_status?->isRented())
                                <a href="{{ route('admin.crm.customers.view', $orderProduct?->order?->customer?->unique_id ?? 0) }}"
                                   class="text-blue-600 hover:underline">
                                    {{ $orderProduct?->order?->customer_name ?? '-' }}
                                </a>
                            @else
                                {{ $orderProduct?->softAssignment?->equipment?->store?->store_name ?? '-' }}
                            @endif
                        </div>
                    </td>

                    {{-- Delivery Date --}}
                    <td class="py-4 px-6 text-center">
                        @php
                            $iconColor = $orderProduct->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                <x-heroicon-o-truck class="w-4 h-4 {{ $iconColor }}" />
                                <span>
                                    {{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : 'N/A' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : ' ' }}
                            </span>
                            @if ($orderProduct->delivery_status === 'Completed')
                                <span class="text-xs text-green-600 font-semibold mt-0.5">✓ Done</span>
                            @endif
                        </div>
                    </td>

                    {{-- Delivery Driver --}}
                    <td class="py-4 px-6 text-center min-w-[8rem]">
                        @if ($orderProduct->deliveryEmployee)
                            <button type="button"
                                class="assign-driver-btn text-xs font-semibold text-blue-700 hover:text-blue-900 hover:underline cursor-pointer"
                                title="Click to reassign delivery driver"
                                data-slot="delivery"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $orderProduct->delivery_by }}"
                                data-current-driver-name="{{ $orderProduct->deliveryEmployee->full_name }}">
                                {{ $orderProduct->deliveryEmployee->full_name }}
                            </button>
                        @else
                            <button type="button"
                                class="assign-driver-btn inline-flex items-center gap-1 text-xs text-blue-600 hover:text-blue-800 font-medium"
                                title="Assign delivery driver"
                                data-slot="delivery"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date, 'M d, y') : '' }}"
                                data-current-driver-id=""
                                data-current-driver-name="">
                                <x-heroicon-o-truck class="w-3.5 h-3.5" />
                                Assign
                            </button>
                        @endif
                    </td>

                    {{-- Return Date --}}
                    <td class="py-4 px-6 text-center">
                        @php
                            $iconColor = $orderProduct->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                <x-heroicon-o-arrow-uturn-left class="w-4 h-4 {{ $iconColor }}" />
                                <span>
                                    {{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : 'N/A' }}
                                </span>
                            </div>
                            <span class="text-xs text-gray-500 mt-1">
                                {{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : ' ' }}
                            </span>
                            @if ($orderProduct->pickup_status === 'Completed')
                                <span class="text-xs text-green-600 font-semibold mt-0.5">✓ Done</span>
                            @endif
                        </div>
                    </td>

                    {{-- Return Driver --}}
                    <td class="py-4 px-6 text-center min-w-[8rem]">
                        @if ($orderProduct->pickupEmployee)
                            <button type="button"
                                class="assign-driver-btn text-xs font-semibold text-purple-700 hover:text-purple-900 hover:underline cursor-pointer"
                                title="Click to reassign return driver"
                                data-slot="return"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id="{{ $orderProduct->pickup_by }}"
                                data-current-driver-name="{{ $orderProduct->pickupEmployee->full_name }}">
                                {{ $orderProduct->pickupEmployee->full_name }}
                            </button>
                        @else
                            <button type="button"
                                class="assign-driver-btn inline-flex items-center gap-1 text-xs text-purple-600 hover:text-purple-800 font-medium"
                                title="Assign return driver"
                                data-slot="return"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct->order?->unique_id }}"
                                data-order-number="{{ $orderProduct->order?->order_number }}"
                                data-customer-name="{{ $orderProduct->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}"
                                data-delivery-date="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date, 'M d, y') : '' }}"
                                data-current-driver-id=""
                                data-current-driver-name="">
                                <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                                Assign
                            </button>
                        @endif
                    </td>

                    <td class="py-4 px-6">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order?->unique_id ?? 0) }}"
                                class="text-sky-600 hover:text-sky-800" title="View Order">
                                @if ($orderProduct?->order?->notes->isNotEmpty())
                                    <x-heroicon-o-book-open class="w-4 h-4" />
                                @else
                                    <x-heroicon-o-eye class="w-4 h-4" />
                                @endif
                            </a>
                        </div>
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
