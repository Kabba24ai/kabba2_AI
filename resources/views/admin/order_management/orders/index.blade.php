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
        @include('admin.partials.formErrors')
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
                <button type="button" id="delete-selected-btn"
                    class="flex items-center gap-2 bg-red-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-700 transition">
                    <x-heroicon-o-trash class="w-4 h-4" />
                    Delete Selected (<span id="delete-selected-count">0</span>)
                </button>
            </div>

        </div>
    </div>

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
                        <td class="px-4 py-3 text-center">{{ $order->payment_type }}</td>
                        <td class="px-4 py-3 text-center">
                            <span
                                class="text-xs font-semibold px-2 py-1 rounded-full
                    {{ $order->status === 'Paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                {{ $order->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">{{ $order->created_at->format(config('app.date.date_format')) }}
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex gap-2 items-center justify-center">
                                <a href="{{ route('admin.order-management.orders.edit', $order->unique_id) }}"
                                    class="text-sky-600 hover:text-sky-800" title="View">
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


@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllCheckbox = document.getElementById('select-all-checkbox');
            const orderCheckboxes = document.querySelectorAll('.order-checkbox');
            const deleteBtn = document.getElementById('delete-selected-btn');
            const deleteCountSpan = document.getElementById('delete-selected-count');

            // Select all functionality
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    orderCheckboxes.forEach(cb => cb.checked = selectAllCheckbox.checked);
                    updateDeleteBtnCount();
                });
            }
            // Update 'Select All' checkbox if any item is unchecked
            orderCheckboxes.forEach(cb => {
                cb.addEventListener('change', function() {
                    selectAllCheckbox.checked = [...orderCheckboxes].every(cb => cb.checked);
                    updateDeleteBtnCount();
                });
            });

            function updateDeleteBtnCount() {
                const count = [...orderCheckboxes].filter(cb => cb.checked).length;
                deleteCountSpan.textContent = count;
            }

            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    const ids = [...orderCheckboxes].filter(cb => cb.checked).map(cb => cb.value);

                    if (ids.length === 0) {
                        if (window.showError) {
                            window.showError('Please select at least one order to delete.',
                                'No orders selected!');
                        } else {
                            alert('Please select at least one order to delete.');
                        }
                        return;
                    }

                    window.showConfirm(
                        `Delete ${ids.length} order(s)? This action cannot be undone!`,
                        'Delete Orders'
                    ).then((result) => {
                        if (result.isConfirmed) {
                            fetch("{{ route('admin.order-management.orders.bulk-delete') }}", {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        unique_ids: ids
                                    })
                                })
                                .then(res => res.json())
                                .then(data => {
                                    if (data.success || (data.message && data.message
                                            .toLowerCase().includes('deleted'))) {
                                        notyf.success('Selected orders have been deleted.',
                                            'Deleted!');
                                        // Remove rows
                                        ids.forEach(function(id) {
                                            const row = document.getElementById(
                                                'order-row-' + id);
                                            if (row) row.remove();
                                        });
                                        // Reset select all and count
                                        if (selectAllCheckbox) selectAllCheckbox.checked =
                                        false;
                                        updateDeleteBtnCount();
                                    } else {
                                        notyf.error(data.message ||
                                            'Could not delete selected orders.', 'Failed!');
                                    }
                                }).catch(() => {
                                    notyf.error('Something went wrong. Please try again.',
                                        'Error!');
                                });
                        }
                    });
                });
            }

        });
    </script>
@endpush
