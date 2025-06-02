@extends('admin.layouts.app')

@section('title', 'Products')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Products</h3>
        <a href="{{ route('admin.product-management.products.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Product
        </a>
    </div>

    {{-- Search and Filters --}}
    <form method="GET" action="{{ route('admin.product-management.products.index') }}" class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {{-- Search Input --}}
            <div class="flex-1 relative">
                <input type="text" name="search" placeholder="Search products..." value="{{ request('search') }}"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
            </div>

            {{-- Select Category --}}
            <select name="category"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">Select Category</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected(request('category') == $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            {{-- Type Dropdown --}}
            <select name="type"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">All Products</option>
                <option value="Rental" @selected(request('type') === 'Rental')>Rental</option>
                <option value="Retail" @selected(request('type') === 'Retail')>Retail</option>
            </select>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            {{-- Table Header --}}
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Product</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Type</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Price</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        W/E Spcl.</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Weekly</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Monthly</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Categories</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-4 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
                @forelse($products as $product)
                    <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800 dark:text-gray-100">{{ $product->title }}</div>
                            <div class="text-gray-500 text-xs truncate">{{ $product->description }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-block rounded-full text-xs px-2 py-1 font-semibold
                                {{ $product->type === 'Rental' ? 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400' }}">
                                {{ $product->type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">${{ number_format($product->price, 2) }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $product->weekend_special ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $product->weekly_price ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $product->monthly_price ?? '-' }}</td>
                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $product->category ?? '-' }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $product->status === 'Published'
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
@endsection

@push('js')
@endpush
