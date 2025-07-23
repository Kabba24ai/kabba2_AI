
<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="px-4 py-3 text-left">Order ID</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Company</th>
                <th class="px-4 py-3 text-center">Product</th>
                <th class="px-4 py-3">Delivery Address</th>
                <th class="px-4 py-3 text-left">Phone</th>
                <th class="px-4 py-3 text-right">Amount</th>
                <th class="px-4 py-3 text-left">Payment Type</th>
                <th class="px-4 py-3 text-center">Payment</th>
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
                    <td class="px-4 py-3 text-left">
                        {!! $order->view_link !!}
                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">{{ $order->customer_name }}</div>
                        <div class="text-gray-500 text-xs">{{ $order->customer?->unique_id ?? '-' }}</div>
                    </td>
                    <td class="px-4 py-3">
                        {{ $order->customer->company_name }}

                        <a
                            @if (!empty($order->customer->company_website)) href="{{ $order->customer->company_website ?? 'javascript:void(0)' }}"  target="_blank" @endif>
                            <div class="text-sm text-gray-500 flex items-center gap-1">
                                <x-heroicon-o-globe-alt class="w-4 h-4 text-gray-400" />
                                <span>
                                    @if (!empty($order->customer->company_website))
                                        {{ $order->customer->company_website }}
                                    @else
                                        -
                                    @endif
                                </span>
                            </div>
                        </a>
                    </td>
                    <td class="px-4 py-3 truncate max-w-xs text-center">{!! $order->products->pluck('product_name')->join('<br> ') !!}</td>
                    <td class="px-4 py-3 truncate max-w-xs ">{{ $order->shippingAddress->address }}</td>
                    <td class="px-4 py-3 text-left ">{{ $order->customer_phone }}</td>
                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</td>
                    <td class="px-4 py-3 text-center">
                        {{ $order->last_payment_type }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        {!! $order->last_payment_badge !!}
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
