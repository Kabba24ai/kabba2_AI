{{-- Product Table --}}
<div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
    <div id="product-loading" class="hidden"></div>
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
        {{-- Table Header --}}
        @include('admin.product_management.products.partials._table-header')
        <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
            @forelse($products as $product)
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                    <td class="text-left px-6 py-4 text-gray-600 dark:text-gray-400">
                        @if ($product->categories && $product->categories->count())
                            {{ $product->categories->pluck('title')->join(', ') }}
                        @else
                            <span class="text-gray-400 italic">None</span>
                        @endif
                    </td>
                    <td class="text-left px-6 py-4">
                        <div class="font-medium text-gray-800 dark:text-gray-100">{{ $product->product_name }}</div>
                        @if ($product->is_on_sale)
                            <span
                                class="ml-0 inline-block rounded bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400 px-2 py-0.5 text-xs font-semibold align-middle">
                                On Sale
                            </span>
                        @endif
                    </td>
                    <td class="text-left px-6 py-4">
                        <span
                            class="inline-block rounded-full text-xs px-2 py-1 font-semibold
                            {{ $product->product_type === 'Rental'
                                ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400'
                                : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' }}">
                            {{ $product->product_type }}
                        </span>
                    </td>
                    <td class="text-left px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency(
                            $product->product_type === 'Retail' ? $product->getRetailPrice() : $product->getRentalPrice('daily'),
                        ) }}
                    </td>
                    <td class="text-left px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->getRentalPrice('weekend')) }}
                    </td>
                    <td class="text-left px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->getRentalPrice('weekly')) }}
                    </td>
                    <td class="text-left px-6 py-4 text-gray-600 dark:text-gray-400">
                        {{ \App\Helpers\CustomHelper::formatCurrency($product->getRentalPrice('monthly')) }}
                    </td>

                    <td class="text-center px-6 py-4">
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                            @if ($product->status === 'Published') bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400
                            @elseif ($product->status === 'Pending') bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400
                            @elseif ($product->status === 'Draft') bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400
                            @else bg-gray-100 text-gray-700 dark:bg-gray-500/10 dark:text-gray-400 @endif">
                            {{ $product->status }}
                        </span>
                    </td>
                    <td class="text-right px-4 py-4 whitespace-nowrap">
                        <div class="inline-flex items-center justify-end gap-2 whitespace-nowrap">
                            {{-- Copy Button --}}
                            <form action="{{ route('admin.product-management.products.copy', $product->unique_id) }}" method="POST" class="inline-flex">
                                @csrf
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-blue-500 hover:text-blue-600 dark:text-blue-400 dark:hover:text-blue-300"
                                    title="Copy Product">
                                    <x-heroicon-o-document-duplicate class="w-5 h-5" />
                                </button>
                            </form>

                            {{-- Edit Button --}}
                            <a href="{{ route('admin.product-management.products.edit', $product->unique_id) }}" target="_blank"
                                class="inline-flex items-center justify-center rounded-md p-1.5 text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300"
                                title="Edit">
                                <x-heroicon-o-pencil class="w-5 h-5" />
                            </a>

                            {{-- Delete Button --}}
                            <form action="{{ route('admin.product-management.products.delete', $product->unique_id) }}"
                                method="POST" class="inline-flex"
                                onsubmit="return confirm('Are you sure you want to delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-md p-1.5 text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300"
                                    title="Delete">
                                    <x-heroicon-o-trash class="w-5 h-5" />
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                        @if ($products)
                            No products found.
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
@if ($products)
<div class="mt-6">
    {{ $products->links() }}
</div>
@endif
