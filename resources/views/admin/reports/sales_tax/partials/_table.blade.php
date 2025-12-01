<div class="shadow rounded-lg overflow-x-auto border-gray-200 bg-white dark:bg-gray-900 mt-6">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm  text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3 text-left">Order ID</th>
                <th class="px-4 py-3 text-center">Order Date</th>
                <th class="px-4 py-3 text-left">Customer</th>

                <th class="px-4 py-3 text-left">Product</th>
                <th class="px-4 py-3 text-left">Payment Method</th>

                <th class="px-4 py-3 text-right">Sub Amount</th>
                <th class="px-4 py-3 text-right">Tax</th>
                <th class="px-4 py-3 text-right">Discount Amount</th>
                <th class="px-4 py-3 text-right">Total Amount</th>

            </tr>
        </thead>
        <div id="order-loading" class="hidden"></div>
        <tbody class="divide-y">
            @forelse ($orders as $order)
                <tr id="order-row-{{ $order->unique_id }}" class="hover:bg-gray-50">

                    <td class="px-4 py-3 text-left">
                        {!! $order->link !!}
                    </td>
                    <td class="px-4 py-3 text-center">
                        {{ \App\Helpers\CustomHelper::formatDate($order->date) }}

                    </td>
                    <td class="px-4 py-3">
                        <div class="font-medium">

                            {{ $order->customer_name }}
                        </div>

                    </td>

                    <td class="px-4 py-3 truncate  text-left">

                        {{ $order->products }}

                    </td>
                    <td class="px-4 py-3 text-center">

                        {{ $order->payment_type }}

                    </td>

                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->subtotal) }}
                    </td>

                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->tax_amount) }}
                    </td>

                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->discount_amount) }}
                    </td>

                    <td class="px-4 py-3 font-semibold text-right">
                        {{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}
                    </td>

                </tr>

            @empty
                <tr>
                    <td colspan="9" class="text-center text-sm text-gray-500 px-4 py-6">
                        @if ($orders)
                            No orders found.
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
@if ($orders)
    <div class="mt-6">
        {{ $orders->links('vendor.pagination.tailwind') }}
    </div>
@endif
