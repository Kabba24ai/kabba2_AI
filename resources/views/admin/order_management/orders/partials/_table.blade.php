
<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3"><input type="checkbox" id="select-all-checkbox" /></th>
                <th class="px-4 py-3 text-left">Order ID</th>
                <th class="px-4 py-3 text-left">Customer</th>
                <th class="px-4 py-3 text-left">Company</th>
                <th class="px-4 py-3 text-left">Product</th>
                <th class="px-4 py-3 text-left">Delivery Address</th>
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

                        @if (!empty($order->customer->company_website))
                            <a href="{{ $order->customer->company_website }}" target="_blank">
                                <div class="text-sm text-brand-500 flex items-center gap-1">
                                    <x-heroicon-o-globe-alt class="w-4 h-4 text-brand-400" />
                                    <span>{{ $order->customer->company_website }}</span>
                                </div>
                            </a>
                        @endif
                    </td>
                    <td class="px-4 py-3 truncate min-w-3xs max-w-3xs text-left">{!! $order->products->pluck('product_name')->join('<br> ') !!}</td>
                    <td class="px-4 py-3 truncate min-w-xs max-w-xs ">{{ $order->shippingAddress->address }}</td>
                    <td class="px-4 py-3 text-left ">{{ $order->customer_phone }}</td>
                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</td>
                    <td class="px-4 py-3 text-center">
                        {{ $order->last_payment_type }}
                    </td>
                    <td class="px-4 py-3 text-center">
                        {!! \App\Helpers\CustomHelper::statusBadge($order->last_payment_status) !!}
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
