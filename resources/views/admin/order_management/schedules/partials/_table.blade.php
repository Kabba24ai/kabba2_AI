<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider border-b">
            <tr>
                <th class="px-4 py-3 text-center">Product Name</th>
                <th class="px-4 py-3 text-center">Order</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Delivery Address</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-center">Equipment</th>
                <th class="px-4 py-3 text-center">Delivery Date</th>
                <th class="px-4 py-3 text-center">Return Date</th>
                <th class="px-4 py-3 text-center">Payment</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <div id="schedule-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orderProducts as $orderProduct)
                <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-left">{{ $orderProduct->product_name }}</td>
                    <td class="px-4 py-3 text-center">
                        {!! $orderProduct->order->view_link !!}
                    </td>
                    <td class="px-4 py-3 text-left">{{ $orderProduct->order->customer_name }}</td>
                    <td class="px-4 py-3 truncate max-w-xs ">
                        {{ $orderProduct->order->shippingAddress->full_address }}</td>
                    <td class="px-4 py-3 text-left ">{{ $orderProduct->order->customer_phone }}</td>
                    <td class="px-4 py-3 text-center">{{ $orderProduct->equipment ?? 'N/A' }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $iconColor = $orderProduct->delivery_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if (!empty($orderProduct->delivery_type))
                                    @if ($orderProduct->delivery_type === 'Truck')
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
                    <td class="px-4 py-3 text-center">
                        @php
                            $iconColor = $orderProduct->pickup_status === 'Completed' ? 'text-green-600' : 'text-yellow-600';
                        @endphp
                        <div class="flex flex-col items-center">
                            <div class="flex items-center justify-center gap-1">
                                @if (!empty($orderProduct->pickup_type))
                                    @if ($orderProduct->pickup_type === 'Truck')
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

                    <td class="px-4 py-3 text-center">
                        @php
                            $status = strtolower($orderProduct->order->last_payment_status);
                            $badgeClasses = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'paid' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                            ];
                            $class = $badgeClasses[$status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $class }}">
                            {{ ucfirst($orderProduct->order->last_payment_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order->unique_id) }}"
                                class="text-sky-600 hover:text-sky-800" title="View" target="_blank">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="text-center text-sm text-gray-500 px-4 py-6">
                        No Schedules found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{-- Pagination --}}
<div class="mt-6">
    {{ $orderProducts->links('vendor.pagination.tailwind') }}
</div>
