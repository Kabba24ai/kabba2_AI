<div class="bg-white shadow-sm rounded-lg overflow-x-auto">
    <table class="min-w-full text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider border-b">
            <tr>
                <th class="px-4 py-3"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="px-4 py-3 text-left">OrderNumber</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3">Address</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-left">Payment</th>
                <th class="px-4 py-3 text-center">Status</th>
                {{-- <th class="px-4 py-3 text-center">Delivery</th>
                <th class="px-4 py-3 text-center">Return</th> --}}
                <th class="px-4 py-3 text-center">Created</th>
                <th class="px-4 py-3 text-center">Actions</th>
            </tr>
        </thead>
        <div id="order-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orders as $order)
                <tr id="order-row-{{ $order->unique_id }}" class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <input type="checkbox" class="order-checkbox" value="{{ $order->unique_id }}" />
                    </td>
                    <td class="px-4 py-3 text-left">{{ $order->order_number }}</td>
                    <td class="px-4 py-3 text-left">{{ $order->customer_name }}</td>
                    <td class="px-4 py-3 truncate max-w-xs text-center">{!! $order->products->pluck('product_name')->join('<br> ') !!}</td>
                    <td class="px-4 py-3 truncate max-w-xs ">{{ $order->shippingAddress->address }}</td>
                    <td class="px-4 py-3 text-left ">{{ $order->customer_phone }}</td>
                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</td>
                    <td class="px-4 py-3 text-center">{{ $order->last_payment_type }}</td>
                    <td class="px-4 py-3 text-center">
                        @php
                            $status = strtolower($order->last_payment_status);
                            $badgeClasses = [
                                'pending' => 'bg-yellow-100 text-yellow-800',
                                'paid' => 'bg-green-100 text-green-800',
                                'failed' => 'bg-red-100 text-red-800',
                            ];
                            $class = $badgeClasses[$status] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 rounded text-xs font-semibold {{ $class }}">
                            {{ ucfirst($order->last_payment_status) }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-center">{{ $order->created_at->format(config('app.date.date_format')) }}
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex gap-2 items-center justify-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                class="text-sky-600 hover:text-sky-800" title="View" target="_blank">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="13" class="text-center text-sm text-gray-500 px-4 py-6">
                        No orders found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
{{-- Pagination --}}
<div class="mt-6">
    {{ $orders->links('vendor.pagination.tailwind') }}
</div>
