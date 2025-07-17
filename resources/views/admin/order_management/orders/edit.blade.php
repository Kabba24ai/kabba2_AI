@extends('admin.layouts.app')

@section('title', 'Edit Order')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Order Header Section --}}
    <div class="bg-white px-4 py-4 rounded-md shadow-sm mb-6">
        <div class="flex flex-wrap items-center justify-between gap-4">

            {{-- Order Info + Customer --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4">
                <h2 class="text-lg font-semibold text-gray-800">
                    <span class="text-gray-700">Order ID:</span> {{ $order->order_number }}
                </h2>
                <span class="text-sm text-gray-600">Customer: {{ $order->customer_name }}</span>
            </div>

            {{-- Payment Status + Refund Button --}}
            <div class="flex flex-wrap items-center gap-2">
                @if ($order->last_payment_type === 'Card')
                    @switch($order->last_payment_status)
                        @case('Completed')
                            <span
                                class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                                <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                                Paid In Full Via - Credit/Debit Card
                            </span>
                            <button
                                class="flex items-center px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded hover:bg-gray-200 transition rounded-lg">
                                <x-heroicon-o-credit-card class="w-4 h-4 mr-1 text-gray-600" />
                                Refund
                            </button>
                        @break

                        @case('Pending')
                            <span
                                class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                                <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                                PENDING PAYMENT
                            </span>
                        @break

                        @case('Failed')
                            <span
                                class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-red-500 text-white rounded-full">
                                <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                                PAYMENT FAILED
                            </span>
                        @break

                        @default
                    @endswitch
                @else
                    @if ($order->last_payment_status === 'Pending')
                        <span
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            PENDING PAYMENT
                        </span>
                        <!-- Add to Account -->
                        <button
                            class="px-4 py-1 text-xs font-semibold bg-green-600 text-white rounded-full hover:bg-green-700">
                            Add to Account
                        </button>
                        <!-- Confirm Payment -->
                        <button
                            id="confirmPaymentBtn"
                            class="px-4 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700">
                            Confirm payment
                        </button>
                    @else
                        <span
                            class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                            <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                            Paid In Full Via - {{ $order->last_payment_type }}
                        </span>
                        <button
                            class="flex items-center px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded hover:bg-gray-200 transition rounded-lg">
                            <x-heroicon-o-credit-card class="w-4 h-4 mr-1 text-gray-600" />
                            Refund
                        </button>
                    @endif
                @endif

            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-orange-500 text-white rounded hover:bg-orange-600">
                    <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4 mr-1" /> Reorder
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                    <x-heroicon-o-envelope class="w-4 h-4 mr-1" /> Email Invoice
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                    <x-heroicon-o-printer class="w-4 h-4 mr-1" /> Print Invoice
                </button>
                <button
                    class="hidden items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded hover:bg-green-600 ">
                    <x-heroicon-o-check class="w-4 h-4 mr-1" /> Save
                </button>
            </div>
        </div>

        {{-- Sub-links under customer --}}
        <div class="mt-2 flex gap-4 text-sm text-blue-600">
            <a href="{{ route('admin.crm.customers.view', $order->customer?->unique_id) }}"
                class="inline-flex items-center hover:underline {{ !$order->customer ? 'pointer-events-none opacity-50 cursor-not-allowed' : '' }}"
                @if (!$order->customer) tabindex="-1" aria-disabled="true" @endif>
                <x-heroicon-o-user class="w-4 h-4 mr-1" /> Customer Details
            </a>
            <a href="#" class="inline-flex items-center hover:underline">
                <x-heroicon-o-link class="w-4 h-4 mr-1" /> Website Login
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- Billing Info --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Billing Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer" />
                </div>
            </div>
            <div class="p-6">
                <div class="text-sm text-gray-700 space-y-2">
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Customer Name:</span>
                        <span class="col-span-2 text-right">{{ $order->billingAddress->full_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Email:</span>
                        <span class="col-span-2 text-right">
                            @if ($order->billingAddress->email ?? false)
                                <a href="mailto:{{ $order->billingAddress->email }}"
                                    class="text-blue-600 hover:underline">{{ $order->billingAddress->email }}</a>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Phone:</span>
                        <span class="col-span-2 text-right">{{ $order->billingAddress->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Billing Address:</span>
                        <span class="col-span-2 text-right">
                            @if ($order->billingAddress && $order->billingAddress->full_address)
                                {{ $order->billingAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    @if ($order->billingAddress && $order->billingAddress->full_address)
                        <div class="grid grid-cols-3">
                            <span></span>
                            <a href="https://maps.google.com/?q={{ urlencode($order->billingAddress->full_address) }}"
                                target="_blank" class="text-blue-600 text-xs hover:underline col-span-2 text-right">See on
                                maps</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Delivery Info --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer" />
                </div>
            </div>
            <div class="p-6">
                @if ($order->deliveryAddress && !$order->deliveryAddress->isSameAs($order->billingAddress))
                    <div class="text-sm text-gray-700 space-y-1">
                        <p><span class="font-medium">Customer Name:</span>
                            {{ $order->deliveryAddress->full_name ?? 'N/A' }}
                        </p>
                        <p><span class="font-medium">Email:</span>
                            @if ($order->deliveryAddress->email ?? false)
                                <a href="mailto:{{ $order->deliveryAddress->email }}"
                                    class="text-blue-600 hover:underline">{{ $order->deliveryAddress->email }}</a>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </p>
                        <p><span class="font-medium">Phone:</span> {{ $order->deliveryAddress->phone ?? 'N/A' }}</p>
                        <p><span class="font-medium">Delivery Address:</span>
                            @if ($order->deliveryAddress && $order->deliveryAddress->full_address)
                                {{ $order->deliveryAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </p>
                        @if ($order->deliveryAddress && $order->deliveryAddress->full_address)
                            <a href="https://maps.google.com/?q={{ urlencode($order->deliveryAddress->full_address) }}"
                                target="_blank" class="text-blue-600 text-xs hover:underline">See on maps</a>
                        @endif
                    </div>
                @else
                    <p class="text-sm text-blue-600">Same as billing</p>
                @endif
            </div>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">History</h3>
                </div>
            </div>
            <div class="p-6">
                <ul class="text-sm text-gray-700 space-y-1">
                    <li class="flex justify-between">
                        <span>• New Order Placed: Sasha Hill</span>
                        <span class="text-xs text-gray-500">5/16/2025 – 08:32 AM</span>
                    </li>
                    <li class="flex justify-between">
                        <span>• Order Paid: Credit / Debit Card</span>
                        <span class="text-xs text-gray-500">5/16/2025 – 08:32 AM</span>
                    </li>
                    <li class="flex justify-between">
                        <span>• Terms Signed</span>
                        <span class="text-xs text-gray-500">5/16/2025 – 08:35 AM</span>
                    </li>
                    <li class="flex justify-between">
                        <span>• Order Delivered</span>
                        <span class="text-xs text-gray-500">5/16/2025 – 03:43 AM</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-md shadow-sm p-6 mb-6 space-y-6">
        <h3 class="text-base font-semibold text-gray-800">Equipment Orders & Delivery Schedule</h3>

        {{-- Equipment Info --}}
        <div class="grid md:grid-cols-2 gap-6">
            <div class="space-y-4">
                @foreach ($order->products as $orderProduct)
                    <div class="flex gap-4">
                        <div class="w-[150px] h-[150px] bg-gray-100 flex items-center justify-center text-gray-400">
                            @if ($orderProduct->product->image_url)
                                <img src="{{ $orderProduct->product->image_url }}"
                                    alt="{{ $orderProduct->product_name }}"
                                    class="object-contain w-full h-full rounded" />
                            @else
                                Image not available
                            @endif
                        </div>
                        <div>
                            <a href="#" class="text-blue-600 font-semibold hover:underline">
                                {{ $orderProduct->product_name }} -
                                {{ ucwords($orderProduct->product_data['product_variant'] ?? '') }}
                            </a>
                            <p class="text-sm text-gray-500">Equipment ID: {{ $orderProduct->sku ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="border rounded p-4 text-sm text-gray-700 space-y-2 bg-gray-50">
                        <div class="flex justify-between">
                            <span>Product Cost(x{{ $orderProduct->quantity ?? 1 }})</span>
                            <span>{{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->price) }}</span>
                        </div>

                        @if (!empty($orderProduct->service_method))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs flex justify-between items-center">
                                    <span class="underline">Service:</span>
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ $orderProduct->service_method }}
                                    </li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($orderProduct->store))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Store:
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ ucfirst($orderProduct->store->store_name) }}</li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($orderProduct->distance_range))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs underline">
                                    Distance Range:
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ ucfirst($orderProduct->distance_range) }}</li>
                                </ul>
                            </div>
                        @endif

                        @if (!empty($orderProduct->service_option))
                            <div class="flex flex-col gap-y-1">
                                <div class="text-xs flex justify-between items-center">
                                    <span class="underline">Delivery:</span>
                                </div>
                                <ul class="flex flex-col">
                                    <li class="text-xs before:content-['-'] before:pr-1">
                                        {{ $orderProduct->service_option }}
                                    </li>
                                </ul>
                            </div>
                        @endif

                        @if ($orderProduct->product_data && count($orderProduct->product_data))
                            <div class="flex justify-between">
                                <span class="underline">Options</span>
                            </div>
                            <ul class="pl-5 list-disc text-gray-600 text-sm">
                                @foreach ($orderProduct->product_data['product_rental_items_prices'] as $rentalKey => $rentalPrice)
                                    <li class="flex justify-between">
                                        <span>
                                            {{ ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
                                            <span
                                                class="text-xs text-gray-400">(x{{ $orderProduct->quantity ?? 1 }})</span>
                                        </span>
                                        <span>
                                            {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                                        </span>
                                    </li>
                                @endforeach
                                @foreach ($orderProduct->product_data['product_option_items'] as $option)
                                    <li class="flex justify-between">
                                        <span>
                                            {{ $option['name'] }}
                                            <span
                                                class="text-xs text-gray-400">(x{{ $orderProduct->quantity ?? 1 }})</span>
                                        </span>
                                        <span>
                                            {{ \App\Helpers\CustomHelper::formatCurrency($option['price'] ?? 0) }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="flex justify-between font-semibold">
                            <span>Qty - {{ $orderProduct->quantity }}</span>
                            <span>
                                Sub Total: {{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->sub_total) }}
                            </span>
                        </div>
                    </div>

                    {{-- @if ($orderProduct->note)
                        <div class="text-sm px-3 py-2 bg-gray-100 rounded text-gray-600 mb-4">
                            {{ $orderProduct->note }}
                        </div>
                    @endif --}}
                @endforeach
            </div>


            {{-- Schedule Panels --}}
            <div class="space-y-6">
                @foreach ($order->products as $orderProduct)
                    <div class="bg-white rounded-lg border border-gray-200">
                        <div class="px-2 py-2 rounded-t-lg border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-semibold text-gray-800 under">{{ $orderProduct->product_name }}
                                </h3>
                                <input type="hidden" class="order-product-unique-id"
                                    value="{{ $orderProduct->unique_id }}">
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="flex items-center justify-between">
                            </div>
                            {{-- Delivery Schedule --}}
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <x-heroicon-s-truck class="w-5 h-5 text-red-500 mr-1" /> Delivery Schedule
                                </div>
                                <div class="bg-white border rounded-xl p-3 flex flex-wrap gap-3 items-center">
                                    <div class="flex flex-nowrap items-center gap-2 w-full mb-1">
                                        <!-- Date -->
                                        <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_date_{{ $orderProduct->unique_id }}">Date</label>
                                            <input type="text" id="delivery_date_{{ $orderProduct->unique_id }}"
                                                data-format="{{ config('app.date.js_date_format') }}"
                                                value="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date) : '' }}"
                                                placeholder="Select date"
                                                class="datepicker delivery_date border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Time -->
                                        <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_time_{{ $orderProduct->unique_id }}">Time</label>
                                            <input type="text" placeholder="Select time" id="delivery_time_{{ $orderProduct->unique_id }}"
                                                value="{{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : '' }}"
                                                class="timepicker delivery_time border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Type (fixed width, non-stretch) -->
                                        <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_type-btn-{{ $orderProduct->unique_id }}">Type</label>
                                            <div id="delivery_type-{{ $orderProduct->unique_id }}" x-data="{ selected: '{{ $orderProduct->delivery_type ?? 'Store' }}', open: false }" class="relative w-full">
                                                <button id="delivery_type-btn-{{ $orderProduct->unique_id }}" type="button" @click="open = !open"
                                                    class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none delivery_type">
                                                    <template x-if="selected === 'Store'">
                                                        <x-heroicon-o-building-storefront
                                                            class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                    <template x-if="selected === 'Truck'">
                                                        <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                    <ul>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'Store'; open = false;  $nextTick(() => $refs.deliveryTypeInput.dispatchEvent(new Event('change')));"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-building-storefront
                                                                    class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'Truck'; open = false; $nextTick(() => $refs.deliveryTypeInput.dispatchEvent(new Event('change')));"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="hidden" name="type" class="delivery_type" :value="selected" x-ref="deliveryTypeInput">
                                            </div>
                                        </div>
                                        <!-- Status -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_status_{{ $orderProduct->unique_id }}">Status</label>
                                            <select id="delivery_status_{{ $orderProduct->unique_id }}"
                                                class="delivery_status border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="Pending"
                                                    {{ ($orderProduct->delivery_status ?? '') === 'Pending' ? 'selected' : '' }}>
                                                    Pending</option>
                                                <option value="Completed"
                                                    {{ ($orderProduct->delivery_status ?? '') === 'Completed' ? 'selected' : '' }}>
                                                    Completed</option>
                                                <option value="Reschedule"
                                                    {{ ($orderProduct->delivery_status ?? '') === 'Reschedule' ? 'selected' : '' }}>
                                                    Reschedule</option>
                                            </select>
                                        </div>
                                        <!-- Location -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_store_id_{{ $orderProduct->unique_id }}">Location</label>
                                            <select id="delivery_store_id_{{ $orderProduct->unique_id }}"
                                                class="delivery_store_id border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="">Select Location</option>
                                                @foreach ($stores as $storeItem)
                                                    <option value="{{ $storeItem->id }}"
                                                        {{ $orderProduct->delivery_store_id == $storeItem->id ? 'selected' : '' }}>
                                                        {{ $storeItem->store_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Technician -->
                                        <div class="flex flex-col items-start min-w-[90px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="delivery_by_{{ $orderProduct->unique_id }}">Technician</label>
                                            <select id="delivery_by_{{ $orderProduct->unique_id }}"
                                                class="delivery_by border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="">Select Technician</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}"
                                                        {{ $orderProduct->delivery_by == $employee->id ? 'selected' : '' }}>
                                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Return Schedule --}}
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <x-heroicon-s-cube class="w-5 h-5 text-[#BB9167] mr-1" /> Return Schedule
                                </div>
                                <div class="bg-white border rounded-xl p-3 flex flex-wrap gap-3 items-center">
                                    <div class="flex flex-wrap items-center gap-2 w-full">
                                        <!-- Date -->
                                        <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_date_{{ $orderProduct->unique_id }}">Date</label>
                                            <input type="text" id="pickup_date_{{ $orderProduct->unique_id }}"
                                                data-format="{{ config('app.date.js_date_format') }}"
                                                placeholder="Select date"
                                                value="{{ $orderProduct->pickup_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->pickup_date) : '' }}"
                                                class="datepicker pickup_date border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Time -->
                                        <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_time_{{ $orderProduct->unique_id }}">Time</label>
                                            <input type="text" id="pickup_time_{{ $orderProduct->unique_id }}"
                                                placeholder="Select time"
                                                value="{{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : '' }}"
                                                class="timepicker pickup_time border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Type (fixed width, non-stretch) -->
                                        <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_type-btn-{{ $orderProduct->unique_id }}">Type</label>
                                            <div id="pickup_type_{{ $orderProduct->unique_id }}" x-data="{ selected: '{{ $orderProduct->pickup_type ?? 'Store' }}', open: false }" class="relative w-full">
                                                <button id="pickup_type-btn-{{ $orderProduct->unique_id }}" type="button" @click="open = !open"
                                                    class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none pickup_type">
                                                    <template x-if="selected === 'Store'">
                                                        <x-heroicon-o-building-storefront
                                                            class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                    <template x-if="selected === 'Truck'">
                                                        <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                    <ul>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'Store'; open = false; $nextTick(() => $refs.pickupTypeInput.dispatchEvent(new Event('change')));"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-building-storefront
                                                                    class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'Truck'; open = false; $nextTick(() => $refs.pickupTypeInput.dispatchEvent(new Event('change')));"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="hidden" name="type" class="pickup_type" :value="selected" x-ref="pickupTypeInput">
                                            </div>
                                        </div>
                                        <!-- Status -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_status_{{ $orderProduct->unique_id }}">Status</label>
                                            <select id="pickup_status_{{ $orderProduct->unique_id }}"
                                                class="pickup_status border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="Pending"
                                                    {{ ($orderProduct->pickup_status ?? '') === 'Pending' ? 'selected' : '' }}>
                                                    Pending</option>
                                                <option value="Completed"
                                                    {{ ($orderProduct->pickup_status ?? '') === 'Completed' ? 'selected' : '' }}>
                                                    Completed</option>
                                                <option value="Reschedule"
                                                    {{ ($orderProduct->pickup_status ?? '') === 'Reschedule' ? 'selected' : '' }}>
                                                    Reschedule</option>
                                            </select>
                                        </div>
                                        <!-- Location -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_store_id_{{ $orderProduct->unique_id }}">Location</label>
                                            <select id="pickup_store_id_{{ $orderProduct->unique_id }}"
                                                class="pickup_store_id border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="">Select Location</option>
                                                @foreach ($stores as $storeItem)
                                                    <option value="{{ $storeItem->id }}"
                                                        {{ $orderProduct->pickup_store_id == $storeItem->id ? 'selected' : '' }}>
                                                        {{ $storeItem->store_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <!-- Technician -->
                                        <div class="flex flex-col items-start min-w-[90px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                for="pickup_by_{{ $orderProduct->unique_id }}">Technician</label>
                                            <select id="pickup_by_{{ $orderProduct->unique_id }}"
                                                class="pickup_by border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="">Select Technician</option>
                                                @foreach ($employees as $employee)
                                                    <option value="{{ $employee->id }}"
                                                        {{ $orderProduct->pickup_by == $employee->id ? 'selected' : '' }}>
                                                        {{ $employee->first_name }} {{ $employee->last_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Status Checklist --}}
                            <div class="border rounded-xl p-4 bg-white">
                                <div class="grid grid-cols-3 gap-8 text-center text-xs font-medium text-gray-700 mb-2">
                                    <!-- Checklist -->
                                    <div class="flex flex-col items-center">
                                        <div>Checklist</div>
                                        <div class="flex justify-center gap-2 mb-1">
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                                        </div>
                                    </div>
                                    <!-- Machine Hours -->
                                    <div class="flex flex-col items-center">
                                        <div>Machine Hours</div>
                                        <div class="flex justify-center gap-2 mb-1">
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                                        </div>
                                    </div>
                                    <!-- Video -->
                                    <div class="flex flex-col items-center">
                                        <div>Video</div>
                                        <div class="flex justify-center gap-2 mb-1">
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span
                                                class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Legend -->
                                <div class="flex items-center justify-center gap-4 text-xs mt-2">
                                    <span class="flex items-center">
                                        <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>Completed
                                    </span>
                                    <span class="font-semibold">|</span>
                                    <span class="flex items-center">
                                        <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span>Pending
                                    </span>
                                    <span class="font-semibold">|</span>
                                    <span class="flex items-center">
                                        <span class="inline-block w-2 h-2 rounded-full bg-gray-400 mr-1"></span>N/A
                                    </span>
                                    <span class="font-semibold">|</span>
                                    <span>D=Delivery</span>
                                    <span class="font-semibold">|</span>
                                    <span>R=Return</span>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                {{-- Status Checklist --}}
                <div class="border rounded-xl p-4 bg-white">
                    <div class="grid grid-cols-2 gap-2 text-center text-xs font-medium text-gray-700 mb-2">
                        <!-- Terms -->
                        <div class="flex flex-col items-center">
                            <div>Terms</div>
                            <div class="flex justify-center mb-1">
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-base font-bold">
                                    <x-heroicon-o-x-mark class="w-4 h-4" />
                                </span>
                            </div>
                        </div>
                        <!-- License -->
                        <div class="flex flex-col items-center">
                            <div>License</div>
                            <div class="flex justify-center mb-1">
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-base font-bold">
                                    <x-heroicon-o-check class="w-4 h-4" />
                                </span>
                            </div>
                        </div>
                        {{-- <!-- Checklist -->
                        <div>
                            <div>Checklist</div>
                            <div class="flex justify-center gap-1 mb-1">
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                            </div>
                        </div>
                        <!-- Machine Hours -->
                        <div>
                            <div>Machine Hours</div>
                            <div class="flex justify-center gap-1 mb-1">
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                            </div>
                        </div>
                        <!-- Video -->
                        <div>
                            <div>Video</div>
                            <div class="flex justify-center gap-1 mb-1">
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                <span
                                    class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                            </div>
                        </div> --}}
                    </div>
                    <!-- Legend -->
                    <div class="flex items-center justify-center gap-4 text-xs mt-2">
                        <span class="flex items-center">
                            <span class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>Completed
                        </span>
                        <span class="font-semibold">|</span>
                        <span class="flex items-center">
                            <span class="inline-block w-2 h-2 rounded-full bg-red-500 mr-1"></span>Pending
                        </span>
                        <span class="font-semibold">|</span>
                        <span class="flex items-center">
                            <span class="inline-block w-2 h-2 rounded-full bg-gray-400 mr-1"></span>N/A
                        </span>
                        <span class="font-semibold">|</span>
                        <span>D=Delivery</span>
                        <span class="font-semibold">|</span>
                        <span>R=Return</span>
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-8 border-t border-gray-300" />
        {{-- Summary & Notes --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div
                class="bg-gray-50 rounded-lg border border-gray-200 p-4 space-y-2 shadow-sm flex flex-col text-sm text-gray-700 pt-4">
                <div class="flex justify-between">
                    <span>Sub-Total:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->subtotal) }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Taxes:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->tax_amount) }}</span>
                </div>
                <div class="flex justify-between font-bold text-gray-900 border-t pt-2">
                    <span>Grand Total:</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</span>
                </div>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-2 shadow-sm flex flex-col">
                <label for="order_note" class="block text-sm font-semibold text-gray-700 mb-1">Order Notes:</label>
                <input type="text" value="{{ $order->order_note }}" name="order_note" id="order_note"
                    class="w-full border rounded px-3 py-2 text-sm text-gray-800" />
                <div class="flex justify-end">
                    <button class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm"
                        id="saveNoteBtn">
                        Save Note
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get the order id (replace with actual variable)
            const orderUniqueId = '{{ $order->unique_id }}';

            // Confirm payment button
            const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            if (confirmPaymentBtn) {
                confirmPaymentBtn.addEventListener('click', function() {
                    showConfirm('Do you want to confirm this payment?', 'Are you sure?').then((result) => {
                        if (result.isConfirmed) {
                            confirmPaymentBtn.disabled = true;
                            confirmPaymentBtn.textContent = 'Processing...';
                            let url = '{{ route('admin.order-management.orders.confirm-payment', ':unique_id') }}';
                            url = url.replace(':unique_id', orderUniqueId);
                            apiFetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({ _method: 'PUT' })
                            })
                            .then(res => {
                                if (res && res.success) {
                                    notyf.success('Payment confirmed!');
                                    setTimeout(() => window.location.reload(), 800);
                                } else {
                                    notyf.error(res && res.message ? res.message : 'Failed to confirm payment.');
                                }
                            })
                            .catch(() => {
                                notyf.error('Failed to confirm payment.');
                            })
                            .finally(() => {
                                confirmPaymentBtn.disabled = false;
                                confirmPaymentBtn.textContent = 'Confirm payment';
                            });
                        }
                    });
                });
            }
            const saveBtn = document.getElementById('saveNoteBtn');
            const noteInput = document.getElementById('order_note');

            saveBtn.addEventListener('click', function() {
                const note = noteInput.value;

                // UI: Disable and show saving...
                saveBtn.disabled = true;
                const originalText = saveBtn.textContent;
                saveBtn.textContent = 'Saving...';

                url = '{{ route('admin.order-management.orders.update-note', ':unique_id') }}';
                url = url.replace(':unique_id', orderUniqueId);

                let data = {
                    order_note: note,
                    _method: 'PUT'
                };

                apiFetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(resData => {
                        notyf.success('Order note updated successfully!');
                    })
                    .finally(() => {
                        saveBtn.disabled = false;
                        saveBtn.textContent = originalText;
                    });
            });

            document.querySelectorAll('.order-product-unique-id').forEach(function(hiddenInput, idx) {
                const container = hiddenInput.closest('.bg-white.rounded-lg.border');
                if (!container) return;

                const orderProductId = hiddenInput.value;

                // Delivery fields
                const deliveryDate = container.querySelector('.delivery_date');
                const deliveryTime = container.querySelector('.delivery_time');
                const deliveryType = container.querySelector('input[type=hidden].delivery_type');
                const deliveryStatus = container.querySelector('.delivery_status');
                const deliveryLocation = container.querySelector('.delivery_store_id');
                const deliveryTechnician = container.querySelector('.delivery_by');

                // Return fields
                const returnDate = container.querySelector('.pickup_date');
                const returnTime = container.querySelector('.pickup_time');
                const returnType = container.querySelector('input[type=hidden].pickup_type');
                const returnStatus = container.querySelector('.pickup_status');
                const returnLocation = container.querySelector('.pickup_store_id');
                const returnTechnician = container.querySelector('.pickup_by');

                // Helper to update only the changed field for delivery/return
                function updateScheduleField(type, field, value) {
                    let url =
                        '{{ route('admin.order-management.orders.update-product-schedule', [':order_unique_id', ':product_unique_id']) }}';
                    url = url.replace(':order_unique_id', orderUniqueId).replace(':product_unique_id', orderProductId);

                    // Map field to DB column
                    let dbField = '';
                    if (type === 'delivery') {
                        dbField = field;
                    } else if (type === 'return') {
                        dbField = field;
                    }

                    let data = {
                        _method: 'PUT',
                        type: type
                    };
                    data[dbField] = value;

                    apiFetch(url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify(data)
                    })
                    .then(() => {
                        notyf.success('Schedule updated!');
                    })
                    .catch(() => {
                        notyf.error('Failed to update schedule.');
                    });
                }

                // Delivery listeners
                if (deliveryDate) {
                    deliveryDate.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_date', deliveryDate.value);
                    });
                }
                if (deliveryTime) {
                    deliveryTime.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_time', deliveryTime.value);
                    });
                }
                if (deliveryType) {
                    deliveryType.addEventListener('change', function() {
                         console.log('Delivery type changed to', deliveryType.value);
                        updateScheduleField('delivery', 'delivery_type', deliveryType.value);
                    });
                }
                if (deliveryStatus) {
                    deliveryStatus.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_status', deliveryStatus.value);
                    });
                }
                if (deliveryLocation) {
                    deliveryLocation.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_store_id', deliveryLocation.value);
                    });
                }
                if (deliveryTechnician) {
                    deliveryTechnician.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_by', deliveryTechnician.value);
                    });
                }

                // Return listeners
                if (returnDate) {
                    returnDate.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_date', returnDate.value);
                    });
                }
                if (returnTime) {
                    returnTime.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_time', returnTime.value);
                    });
                }
                if (returnType) {
                    returnType.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_type', returnType.value);
                    });
                }
                if (returnStatus) {
                    returnStatus.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_status', returnStatus.value);
                    });
                }
                if (returnLocation) {
                    returnLocation.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_store_id', returnLocation.value);
                    });
                }
                if (returnTechnician) {
                    returnTechnician.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_by', returnTechnician.value);
                    });
                }
            });
        });
    </script>
@endpush
