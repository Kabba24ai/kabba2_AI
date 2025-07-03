@extends('admin.layouts.app')

@section('title', 'Orders')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Orders Management</h3>
        <a href="#"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + New Order
        </a>
    </div>

    {{-- Filters Row --}}
    <div class="bg-white p-4 rounded-md shadow-sm mb-6">
        <div class="flex flex-wrap items-end gap-4">

            {{-- Search Name --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Name</label>
                <div class="relative">
                    <x-heroicon-o-magnifying-glass
                        class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" />
                    <input type="text" placeholder="Customer name..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm w-48 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            {{-- Search Phone --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search Phone</label>
                <div class="relative">
                    <x-heroicon-o-phone class="absolute w-4 h-4 text-gray-400 left-3 top-1/2 transform -translate-y-1/2" />
                    <input type="text" placeholder="Phone number..."
                        class="pl-10 pr-4 py-2 border border-gray-300 rounded-md text-sm w-48 focus:ring-blue-500 focus:border-blue-500" />
                </div>
            </div>

            {{-- Category --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                    <option>All Categories</option>
                    <option>Excavators</option>
                    <option>Generators</option>
                </select>
            </div>

            {{-- Payment Method --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label>
                <select
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                    <option>All Methods</option>
                    <option>Card</option>
                    <option>Cash</option>
                    <option>Transfer</option>
                </select>
            </div>

            {{-- Payment Status --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Payment Status</label>
                <select
                    class="border border-gray-300 rounded-md px-3 py-2 text-sm w-48 focus:ring-blue-500 focus:border-blue-500">
                    <option>All Status</option>
                    <option>Paid</option>
                    <option>Pending</option>
                    <option>Failed</option>
                </select>
            </div>

            {{-- Delete Button --}}
            <div class="ml-auto">
                <button type="button"
                    class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition">
                    <x-heroicon-o-trash class="w-4 h-4" />
                    Delete Selected (0)
                </button>
            </div>

        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
        <table class="min-w-full text-sm text-left whitespace-nowrap">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider border-b">
                <tr>
                    <th class="px-4 py-3"><input type="checkbox" /></th>
                    <th class="px-4 py-3">OrderNumber</th>
                    <th class="px-4 py-3">Customer</th>
                    <th class="px-4 py-3">Product</th>
                    <th class="px-4 py-3">Address</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Amount</th>
                    <th class="px-4 py-3">Payment</th>
                    <th class="px-4 py-3">Status</th>
                    {{-- <th class="px-4 py-3">Delivery</th>
                    <th class="px-4 py-3">Return</th> --}}
                    <th class="px-4 py-3">Created</th>
                    <th class="px-4 py-3">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse ($orders as $order)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3"><input type="checkbox" /></td>
                        <td class="px-4 py-3 text-gray-900 font-medium">{{ $order->order_number }}</td>
                        <td class="px-4 py-3">{{ $order->customer_name }}</td>
                        <td class="px-4 py-3 truncate max-w-xs">{{ $order->products->pluck('product_name')->join('<br> ') }}</td>
                        <td class="px-4 py-3 truncate max-w-xs">{{ $order->shippingAddress->address }}</td>
                        <td class="px-4 py-3">{{ $order->customer_phone  }}</td>
                        <td class="px-4 py-3 font-semibold">${{ number_format($order->grand_total, 2) }}</td>
                        <td class="px-4 py-3">{{ $order->payment_type }}</td>
                        <td class="px-4 py-3">
                            <span
                                class="text-xs font-semibold px-2 py-1 rounded-full
                            {{ $order->status === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        {{-- <td class="px-4 py-3 flex items-center gap-1 text-blue-600">
                            <x-heroicon-o-truck class="w-4 h-4" />
                            <span>{{ $order->delivery_date }}</span>
                        </td>
                        <td class="px-4 py-3 flex items-center gap-1 text-yellow-500">
                            <x-heroicon-o-archive-box class="w-4 h-4" />
                            <span>{{ $order->return_date }}</span>
                        </td> --}}
                        <td class="px-4 py-3">{{ $order->created_at->format('n/j/Y') }}</td>
                        <td class="px-4 py-3 flex gap-2 items-center">
                            <a href="{{ route('admin.order-management.orders.edit', $order->id) }}" class="text-sky-600 hover:text-sky-800"
                                title="View">
                                <x-heroicon-o-eye class="w-4 h-4" />
                            </a>
                            {{-- <a href="#" class="text-brand-600 hover:text-brand-800" title="Edit">
                                <x-heroicon-o-pencil-square class="w-4 h-4" />
                            </a>
                            <form method="POST" action="#" onsubmit="return confirm('Are you sure?')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800" title="Delete">
                                    <x-heroicon-o-trash class="w-4 h-4" />
                                </button>
                            </form> --}}
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

@endsection

@push('js')
@endpush
