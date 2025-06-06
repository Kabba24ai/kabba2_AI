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
        <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded flex items-center gap-2">
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
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full divide-y divide-gray-200" style="min-width: 1800px;">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-40">
                                Product Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-20">
                                ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Customer Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider"
                                style="width: 175px;">Delivery Address</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-28">
                                Phone</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                Equip. ID</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Equip. Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Delivery Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-32">
                                Return Date</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                Payment</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-24">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($schedules as $index => $item)
                            <tr class="{{ $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' }} hover:bg-gray-50">
                                <td class="px-4 py-4 w-40 text-sm font-medium text-gray-900">{{ $item->product_name }}</td>
                                <td class="px-4 py-4 w-20 text-sm text-blue-600 font-medium">{{ substr($item->id, -4) }}
                                </td>
                                <td class="px-4 py-4 w-32 text-sm text-gray-900">{{ $item->customer_name }}</td>
                                <td class="px-4 py-4 text-sm text-gray-900" style="width: 175px;">
                                    <div class="truncate" title="{{ $item->delivery_address }}">
                                        {{ $item->delivery_address }}</div>
                                </td>
                                <td class="px-4 py-4 w-28 text-sm text-gray-900">{{ $item->phone }}</td>
                                <td class="px-4 py-4 w-24 text-sm text-gray-900">{{ $item->equip_id }}</td>
                                <td class="px-4 py-4 w-32 text-sm text-gray-900">{{ $item->equip_name }}</td>

                                <!-- Delivery Date with Icon -->
                                <td class="px-4 py-4 w-32 text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        @if ($item->delivery_mode === 'truck')
                                            <x-heroicon-o-truck
                                                class="w-5 h-5 {{ $item->delivery_status === 'completed' ? 'text-blue-600' : 'text-yellow-500' }}" />
                                        @else
                                            <x-heroicon-o-building-storefront
                                                class="w-5 h-5 {{ $item->delivery_status === 'completed' ? 'text-blue-600' : 'text-yellow-500' }}" />
                                        @endif
                                        <span>{{ $item->delivery_date }} - {{ $item->delivery_time }}</span>
                                    </div>
                                </td>

                                <!-- Return Date with Icon -->
                                <td class="px-4 py-4 w-32 text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        @if ($item->return_mode === 'truck')
                                            <x-heroicon-o-truck
                                                class="w-5 h-5 {{ $item->return_status === 'completed' ? 'text-blue-600' : 'text-yellow-500' }}" />
                                        @else
                                            <x-heroicon-o-building-storefront
                                                class="w-5 h-5 {{ $item->return_status === 'completed' ? 'text-blue-600' : 'text-yellow-500' }}" />
                                        @endif
                                        <span>{{ $item->return_date }} - {{ $item->return_time }}</span>
                                    </div>
                                </td>

                                <!-- Payment Status -->
                                <td class="px-4 py-4 w-24">
                                    <span
                                        class="inline-flex px-2 py-1 text-xs font-medium rounded-full
                                    {{ $item->payment_status === 'paid' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ strtoupper($item->payment_status) }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-4 w-24 text-sm font-medium">
                                    <div class="flex items-center space-x-2">
                                        <a href="#"
                                            class="text-green-600 hover:text-green-900 p-1 hover:bg-green-50 rounded"
                                            title="Edit / Notes">
                                            <x-heroicon-o-pencil class="w-4 h-4" />
                                        </a>
                                        <button onclick="confirm('Are you sure?')"
                                            class="text-red-600 hover:text-red-900 p-1 hover:bg-red-50 rounded"
                                            title="Delete Order">
                                            <x-heroicon-o-trash class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="text-sm text-gray-700">
                    Showing {{ $schedules->firstItem() }} to {{ $schedules->lastItem() }} of {{ $schedules->total() }}
                    records
                </div>
                <div>
                    {{ $schedules->links() }}
                </div>
            </div>
        </div>
    </div>

@endsection

@push('js')
@endpush
