<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 border-b border-gray-200 font-semibold text-gray-700">
            <tr>
                <th class="py-4 px-6 text-left">Product</th>
                <th class="py-4 px-6 text-center">Order</th>
                <th class="py-4 px-6 text-left">Customer</th>
                <th class="py-4 px-6 text-left">Phone</th>
                <th class="py-4 px-6 text-left">Delivery Address</th>
                <th class="py-4 px-6 text-center">Equipment</th>
                <th class="py-4 px-6 text-center">Equipment Id</th>
                <th class="py-4 px-6 text-center">Equip. Location</th>
                <th class="py-4 px-6 text-center">Delivery | Return</th>
                <th class="py-4 px-6 text-center">Delivery Date</th>
                <th class="py-4 px-6 text-center">Return Date</th>
                <th class="py-4 px-6 text-center">Payment</th>
                <th class="py-4 px-2 text-center w-10"></th>
            </tr>
        </thead>
        <div id="schedule-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                @php
                    // Determine equipment's physical store
                    $equipStoreId = null;
                    if ($orderProduct->equipment && !$orderProduct->equipment->current_status?->isRented()) {
                        $equipStoreId = $orderProduct->equipment->store_id;
                    } elseif ($orderProduct->softAssignment?->equipment) {
                        $equipStoreId = $orderProduct->softAssignment->equipment->store_id;
                    }

                    // Conflict: In-Store delivery where equipment isn't at the pickup store
                    $hasStoreConflict =
                        $orderProduct->delivery_transport_mode === 'Store'
                        && $orderProduct->delivery_status === 'Pending'
                        && $equipStoreId !== null
                        && $equipStoreId !== $orderProduct->delivery_store_id;

                    // Pickup | Return column values
                    // Truck delivery (pending): effective pickup = equipment's physical location
                    //   (dispatcher loads from wherever the machine is, not the customer's chosen store)
                    // In-store or completed delivery: use the customer's chosen delivery store
                    $deliveryPending = $orderProduct->delivery_status === 'Pending';
                    if ($orderProduct->delivery_transport_mode === 'Truck' && $deliveryPending) {
                        $equipPhysicalStoreName = null;
                        if ($orderProduct->equipment && !$orderProduct->equipment->current_status?->isRented()) {
                            $equipPhysicalStoreName = $orderProduct->equipment->store?->store_name;
                        } elseif ($orderProduct->softAssignment?->equipment) {
                            $equipPhysicalStoreName = $orderProduct->softAssignment->equipment->store?->store_name;
                        }
                        $pickupStoreName = $equipPhysicalStoreName ?? $orderProduct->deliveryStore?->store_name;
                    } else {
                        $pickupStoreName = $orderProduct->deliveryStore?->store_name;
                    }
                    $returnStoreName = $orderProduct->pickupStore?->store_name ?? null;
                @endphp
                <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">

                    {{-- Product --}}
                    <td class="py-4 px-6 text-left min-w-3xs max-w-3xs">
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
                                    $tooltipHtml .= '<div class="flex gap-2"><span>•</span><span>' . e($cat->title) . '</span></div>';
                                }
                            @endphp
                            <span class="tooltip-trigger block text-blue-600 cursor-pointer text-xs"
                                data-tooltip-html="{{ $tooltipHtml }}">
                                Categories ({{ $count }})
                            </span>
                        @endif
                    </td>

                    {{-- Order --}}
                    <td class="py-4 px-6 text-center">
                        {!! $orderProduct->order?->view_link ?? '-' !!}
                    </td>

                    {{-- Customer --}}
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

                    {{-- Phone --}}
                    <td class="py-4 px-6 text-left">{{ $orderProduct->order?->shippingAddress?->phone ?? '-' }}</td>

                    {{-- Delivery Address (truncated) --}}
                    <td class="py-4 px-6 truncate min-w-[160px] max-w-[200px]"
                        title="{{ $orderProduct->order?->shippingAddress?->full_address }}">
                        {{ $orderProduct->order?->shippingAddress?->full_address ?? '-' }}
                    </td>

                    {{-- Equipment --}}
                    <td class="py-4 px-6 text-center">
                        @php
                            $preferredCategoryId = $orderProduct->product?->categories?->first()?->id;
                        @endphp
                        @if ($orderProduct?->equipment?->current_status?->isRented())
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}"
                                data-category-id="{{ $preferredCategoryId ?? '' }}"
                                data-product-name="{{ $orderProduct->product_name }}">
                                {{ isset($orderProduct->equipment_details['equipment_name']) ? $orderProduct->equipment_details['equipment_name'] : 'Assign' }}
                            </button>
                        @else
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}"
                                data-category-id="{{ $preferredCategoryId ?? '' }}"
                                data-product-name="{{ $orderProduct->product_name }}">
                                {{ $orderProduct->softAssignment?->equipment?->equipment_name ?: 'Assign' }}
                            </button>
                        @endif
                    </td>

                    {{-- Equipment Id --}}
                    <td class="py-4 px-6 text-center">
                        @if ($orderProduct?->equipment?->current_status?->isRented())
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ isset($orderProduct->equipment_details['equipment_id']) ? $orderProduct->equipment_details['equipment_id'] : '-' }}
                            </span>
                        @elseif ($orderProduct?->equipment_id && $orderProduct?->equipment)
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ $orderProduct->equipment->equipment_id ?? '-' }}
                            </span>
                            @if ($orderProduct->equipment->current_status?->isDamaged())
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 border border-red-200 tracking-wide">DAMAGED</span>
                                </div>
                            @endif
                        @else
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ $orderProduct->softAssignment?->equipment?->equipment_id ?? '-' }}
                            </span>
                            @if ($orderProduct->softAssignment?->equipment?->current_status?->isDamaged())
                                <div class="mt-1">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-bold bg-red-100 text-red-700 border border-red-200 tracking-wide">DAMAGED</span>
                                </div>
                            @endif
                        @endif
                    </td>

                    {{-- Equip. Location (physical machine location) --}}
                    <td class="py-4 px-6 text-center">
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

                            {{-- In-Store conflict warning: equipment not at the pickup store --}}
                            @if ($hasStoreConflict)
                                <span class="inline-flex items-center" title="Transfer required — equipment is not at the pickup store">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                                        <path d="M12 9v4"/><path d="M12 17h.01"/>
                                    </svg>
                                </span>
                            @endif
                        </div>
                    </td>

                    {{-- Pickup | Return --}}
                    <td class="py-4 px-6 text-center">
                        <div class="flex flex-col items-center gap-0.5 leading-tight">
                            @if ($deliveryPending)
                                {{-- Delivery is next: Pickup Store = prominent --}}
                                <span class="text-gray-700">{{ $pickupStoreName ?? '-' }}</span>
                                <span class="text-xs text-gray-500">{{ $returnStoreName ?? '-' }}</span>
                            @else
                                {{-- Return is next: Return Store = prominent --}}
                                <span class="text-xs text-gray-500">{{ $pickupStoreName ?? '-' }}</span>
                                <span class="text-gray-700">{{ $returnStoreName ?? '-' }}</span>
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

                    {{-- Return Date --}}
                    <td class="py-4 px-6 text-center">
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
                        </div>
                    </td>

                    {{-- Payment --}}
                    <td class="py-4 px-6 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order?->last_payment_status) !!}
                    </td>

                    {{-- Actions (no header text) --}}
                    <td class="py-4 px-2 text-center">
                        <div class="flex gap-2 items-center justify-center">
                            @if (!empty($orderProduct->equipment_id))
                                {{--
                                <button
                                    type="button"
                                    class="text-sky-600 hover:text-sky-800"
                                    onclick="openAIScheduleAdvisorModal({{ $orderProduct->id }})"
                                    title="AI Schedule Advisor"
                                    >
                                    <x-heroicon-o-sparkles class="w-4 h-4" />
                                </button> --}}
                            @endif

                            <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order?->unique_id ?? 0) }}"
                                class="text-sky-600 hover:text-sky-800" title="View">
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
                            No schedules found.
                        @else
                            <span class="text-gray-400 italic">inhale… exhale… bringing your data to life…</span>
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
