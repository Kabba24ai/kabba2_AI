{{-- Product Table --}}
<div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        {{-- Table Header --}}
        @include('admin.product_management.products.partials._table-header')

        <div id="product-loading" class="hidden"></div>
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $product->product_name }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="inline-block rounded-full text-xs px-2 py-1 font-semibold
                            {{ $product->product_type === 'Rental'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                                : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' }}">
                            {{ $product->product_type }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->retail_price ?? $product->rental_daily) }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->rental_weekend) }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->rental_weekly) }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->rental_monthly) }}
                    </td>
                    <td class="px-6 py-4 text-gray-600 dark:text-gray-400">
                        @if ($product->categories && $product->categories->count())
                            {{ $product->categories->pluck('title')->join(', ') }}
                        @else
                            <span class="text-gray-400 italic">None</span>
                        @endif
                    </td>
                    <td class="px-6 py-4">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if ($product->status === 'Published') bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400
                            @elseif ($product->status === 'Pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400
                            @elseif ($product->status === 'Draft') bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400
                            @else bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400 @endif">
                            {{ $product->status }}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-right space-x-2">
                        {{-- Edit Button --}}
                        <a href="{{ route('admin.product-management.products.edit', $product->unique_id) }}"
                            class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300"
                            title="Edit">
                            <x-heroicon-o-pencil-square class="w-5 h-5" />
                        </a>

                        {{-- Delete Button --}}
                        <form action="{{ route('admin.product-management.products.delete', $product->unique_id) }}"
                            method="POST" class="inline"
                            onsubmit="return confirm('Are you sure you want to delete this product?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                title="Delete">
                                <x-heroicon-o-trash class="w-5 h-5" />
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                        No products found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
<div class="mt-6">
    {{ $products->links() }}
</div>
