@extends('admin.layouts.app')

@section('title', 'Products')

@push('css')
@endpush

@section('content')


    @include('flash::message')

    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('admin.product-management.products.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Product
        </a>

        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Products</h3>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            {{-- Table Header --}}
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Title</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Price</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-4 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $product->id }}</td>
                        <td class="px-6 py-4 font-medium text-gray-800 dark:text-gray-100">{{ $product->title }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">${{ number_format($product->price, 2) }}</td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $product->status === 'Active'
                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }}">
                                {{ $product->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="{{ route('admin.product-management.products.edit', $product->id) }}"
                                class="text-brand-500 hover:text-brand-600 dark:text-brand-400 dark:hover:text-brand-300 font-medium">
                                Edit
                            </a>
                            <form action="{{ route('admin.product-management.products.delete', $product->id) }}"
                                method="POST" class="inline"
                                onsubmit="return confirm('Are you sure you want to delete this product?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="text-red-500 hover:text-red-600 dark:text-red-400 dark:hover:text-red-300 font-medium">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
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
@endsection

@push('js')
@endpush
