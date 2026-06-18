{{-- Detail grid — paginated. Replaced via AJAX on filter change. --}}

<div class="shadow rounded-lg overflow-x-auto border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm text-left whitespace-nowrap">
        <thead class="bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-300">
            <tr>
                <th class="px-4 py-3">Date</th>
                <th class="px-4 py-3">Transaction #</th>
                <th class="px-4 py-3">Store</th>
                <th class="px-4 py-3">Customer</th>
                <th class="px-4 py-3">Product</th>
                <th class="px-4 py-3">Category</th>
                <th class="px-4 py-3">Type</th>
                <th class="px-4 py-3 text-right">Qty</th>
                <th class="px-4 py-3 text-right">Unit Price</th>
                <th class="px-4 py-3 text-right">Discount</th>
                <th class="px-4 py-3 text-right">Extended</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
            @forelse ($grid as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-800/50">
                    <td class="px-4 py-3 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatDate($row->transaction_date) }}
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('admin.order-management.orders.edit', $row->order_unique_id) }}"
                           class="text-brand-500 hover:underline font-medium">
                            #{{ $row->transaction_number }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300">{{ $row->store_name }}</td>
                    <td class="px-4 py-3 font-medium text-gray-900 dark:text-white max-w-[180px] truncate">
                        {{ $row->customer_name }}
                    </td>
                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 max-w-[200px] truncate">
                        {{ $row->product_name }}
                    </td>
                    <td class="px-4 py-3 text-gray-500 dark:text-gray-400">{{ $row->category_name }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            {{ $row->item_type === 'Rental'  ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300' : '' }}
                            {{ $row->item_type === 'Retail'  ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : '' }}
                            {{ $row->item_type === 'Service' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : '' }}
                            {{ !in_array($row->item_type, ['Rental','Retail','Service']) ? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' : '' }}
                        ">
                            {{ $row->item_type }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">{{ $row->quantity }}</td>
                    <td class="px-4 py-3 text-right text-gray-700 dark:text-gray-300">
                        {{ \App\Helpers\CustomHelper::formatCurrency($row->unit_price) }}
                    </td>
                    <td class="px-4 py-3 text-right text-red-600 dark:text-red-400">
                        @if ($row->discount > 0)- {{ \App\Helpers\CustomHelper::formatCurrency($row->discount) }}@else —@endif
                    </td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900 dark:text-white">
                        {{ \App\Helpers\CustomHelper::formatCurrency($row->extended_amount) }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="px-4 py-12 text-center text-gray-400 dark:text-gray-500">
                        No transactions match the selected filters.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($grid->hasPages())
    <div class="mt-4">
        {{ $grid->withQueryString()->links() }}
    </div>
@endif
