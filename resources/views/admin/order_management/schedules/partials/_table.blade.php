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
                <th class="py-4 px-6 text-center">Return Date</th>
                <th class="py-4 px-6 text-center">Payment</th>
                <th class="py-4 px-6 text-center">Actions</th>
            </tr>
        </thead>
        <div id="schedule-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
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


                    <td class="py-4 px-6 text-center">
                        {!! $orderProduct->order->view_link !!}
                    </td>
                    <td class="py-4 px-6 text-left">

                        <div class="font-medium">
                            {{ $orderProduct->order->customer_name }}
                        </div>

                        @php
                            $customer = $orderProduct->order->customer;
                        @endphp

                        @if ($customer && $customer->company_name)
                            <div class="text-xs text-gray-500 mt-1 ">

                                @if (!empty($customer->company_website))
                                    <!-- With website: underline + clickable -->
                                    <a href="{{ $customer->company_website }}" class="underline ">
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



                    <td class="py-4 px-6 truncate min-w-xs max-w-xs">
                        {{ $orderProduct->order->shippingAddress?->full_address ?? '-' }}</td>
                    <td class="py-4 px-6 text-left ">{{ $orderProduct->order->shippingAddress?->phone ?? '-' }}</td>
                    <td class="py-4 px-6 text-center">
                        @if ($orderProduct?->checklistQuestions->isNotEmpty())
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}">
                                {{ isset($orderProduct->equipment_details['equipment_name']) ? $orderProduct->equipment_details['equipment_name'] : 'Assign' }}
                            </button>
                        @else
                            <button type="button" class="text-blue-600 underline equipment-assign-btn"
                                data-order-product-unique-id="{{ $orderProduct->unique_id }}"
                                data-order-unique-id="{{ $orderProduct?->order?->unique_id }}"
                                data-order-id="{{ $orderProduct?->order?->order_number }}"
                                data-customer-name="{{ $orderProduct?->order?->customer_name }}"
                                data-product-name="{{ $orderProduct->product_name }}">
                                {{ $orderProduct->softAssignment?->equipment->equipment_name ?: 'Assign' }}
                            </button>
                        @endif
                    </td>
                    <td class="py-4 px-6 text-center">
                        @if ($orderProduct?->checklistQuestions->isNotEmpty())
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ isset($orderProduct->equipment_details['equipment_id']) ? $orderProduct->equipment_details['equipment_id'] : '-' }}
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-mono font-medium bg-gray-100 text-gray-800 border">
                                {{ $orderProduct->softAssignment?->equipment->equipment_id ?? '-' }}
                            </span>
                        @endif
                    </td>

                    <td class="py-4 px-6 text-center">
                        <div class="inline-flex items-center gap-1">
                            @if ($orderProduct?->checklistQuestions->isNotEmpty())
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
                    <td class="py-4 px-6 text-center">
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
                    <td class="py-4 px-6 text-center">
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

                    <td class="py-4 px-6 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($orderProduct->order->last_payment_status) !!}
                    </td>
                    <td class="py-4 px-6">
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

                            <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order->unique_id) }}"
                                class="text-sky-600 hover:text-sky-800" title="View" >
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
                    <td colspan="12" class="text-center text-sm text-gray-500 px-4 py-6">
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

