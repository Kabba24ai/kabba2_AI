@extends('admin.layouts.app')

@section('title', 'Schedules')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    <!-- Page Header -->
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold flex items-center gap-2">
            <x-heroicon-o-calendar-days class="w-6 h-6 text-blue-600" />
            Schedule Management
        </h1>
        <button onclick="location.reload()"
            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
            <x-heroicon-o-arrow-path class="w-5 h-5" />
            Reload
        </button>
    </div>

    <div class="bg-white p-4 rounded-md shadow-sm space-y-4">
        <!-- Row 1: Inputs & Selects -->
        <div class="flex flex-wrap gap-4 items-center">
            <!-- Customer Name Search -->
            <div class="relative">
                <x-heroicon-o-magnifying-glass
                    class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                <input type="text" placeholder="Search by customer name..."
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-64 focus:outline-none focus:ring-2 focus:ring-blue-500" />
            </div>

            <!-- Phone Search -->
            <div class="relative">
                <x-heroicon-o-phone class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 transform -translate-y-1/2" />
                <input type="text"
                    class="masked-phone pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm w-48 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="(xxx) xxx-xxxx" />
            </div>


            <!-- Category Dropdown -->
            <select
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option>All Categories</option>
                <option>Excavators</option>
                <option>Generators</option>
            </select>

            <!-- Payment Status Dropdown -->
            <select
                class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <option>All Payment Status</option>
                <option>Paid</option>
                <option>Pending</option>
            </select>
        </div>

        <!-- Row 2: Checkboxes & Date Filter -->
        <div class="flex flex-wrap items-center gap-6 text-sm text-gray-700">
            <!-- Show Filter -->
            <div class="flex items-center gap-2">
                <span class="font-medium">Show:</span>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Delivery
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Return
                </label>
            </div>

            <!-- Mode Filter -->
            <div class="flex items-center gap-2">
                <span class="font-medium">Mode:</span>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Truck
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    In Store
                </label>
            </div>

            <!-- Store Filter -->
            <div class="flex items-center gap-2">
                <span class="font-medium">Stores:</span>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Charlotte
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" checked class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Bon Aqua
                </label>
                <label class="flex items-center gap-1">
                    <input type="checkbox" class="text-blue-600 focus:ring-blue-500 rounded border-gray-300">
                    Rescheduled
                </label>
            </div>

            <!-- Date Dropdown -->
            <div>
                <select
                    class="border border-gray-300 rounded px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <option>All Dates</option>
                    <option>Today</option>
                    <option>This Week</option>
                    <option>This Month</option>
                </select>
            </div>
        </div>
   </div>

   <div class="mx-auto py-6">
       <div class="bg-white shadow-sm rounded-lg overflow-x-auto">
           <table class="min-w-full text-sm text-left whitespace-nowrap">
               <thead class="bg-gray-50 text-gray-600 uppercase text-xs tracking-wider border-b">
                   <tr>
                       <th class="px-4 py-3 text-center">Product Name</th>
                       <th class="px-4 py-3 text-center">OrderNumber</th>
                       <th class="px-4 py-3 text-left">Customer</th>
                       <th class="px-4 py-3 text-left">Delivery Address</th>
                       <th class="px-4 py-3 text-left">Phone</th>
                       <th class="px-4 py-3 text-left">Equipment</th>
                       <th class="px-4 py-3 text-center">Delivery Date</th>
                       <th class="px-4 py-3 text-center">Return Date</th>
                       <th class="px-4 py-3 text-center">Payment</th>
                       <th class="px-4 py-3 text-center">Actions</th>
                   </tr>
               </thead>
               <tbody class="divide-y">
                   @forelse ($orderProducts as $orderProduct)
                       <tr id="order-row-{{ $orderProduct->id }}" class="hover:bg-gray-50">
                           <td class="px-4 py-3 text-left">{{ $orderProduct->product_name }}</td>
                           <td class="px-4 py-3 text-center">
                             {!! $orderProduct->order->view_link !!}
                           </td>
                           <td class="px-4 py-3 text-left">{{ $orderProduct->order->customer_name }}</td>
                           <td class="px-4 py-3 truncate max-w-xs ">{{ $orderProduct->order->shippingAddress->full_address }}</td>
                           <td class="px-4 py-3 text-left ">{{ $orderProduct->order->customer_phone }}</td>
                           <td class="px-4 py-3 text-left">{{ $orderProduct->equipment ?? 'N/A' }}</td>
                           <td class="px-4 py-3 text-center">{{ $orderProduct->schedule_start_date ? $orderProduct->schedule_start_date : 'N/A' }}</td>
                           <td class="px-4 py-3 text-center">{{ $orderProduct->schedule_end_date ? $orderProduct->schedule_end_date : 'N/A' }}</td>
                           <td class="px-4 py-3 text-center">{{ $orderProduct->order->payment_status }}</td>
                           <td class="px-4 py-3">
                               <div class="flex gap-2 items-center justify-center">
                                   <a href="{{ route('admin.order-management.orders.edit', $orderProduct->order->unique_id) }}"
                                       class="text-sky-600 hover:text-sky-800" title="View">
                                       <x-heroicon-o-eye class="w-4 h-4" />
                                   </a>
                               </div>
                           </td>
                       </tr>
                   @empty
                       <tr>
                           <td colspan="10" class="text-center text-sm text-gray-500 px-4 py-6">
                               No orders found.
                           </td>
                       </tr>
                   @endforelse
               </tbody>
           </table>
       </div>
       {{-- Pagination --}}
       <div class="mt-6">
           {{ $orderProducts->links('vendor.pagination.tailwind') }}
       </div>
    </div>
@endsection

@push('js')
@endpush
