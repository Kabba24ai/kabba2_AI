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
                        <button class="px-4 py-1 text-xs font-semibold bg-green-600 text-white rounded-full hover:bg-green-700">
                            Add to Account
                        </button>
                        <!-- Confirm Payment -->
                        <button class="px-4 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700">
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
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded hover:bg-green-600">
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
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-date">Date</label>
                                            <input id="delivery-date" type="text"
                                                data-format="{{ config('app.date.js_date_format') }}"
                                                placeholder="Select date"
                                                class="datepicker border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Time -->
                                        <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-time">Time</label>
                                            <input id="delivery-time" type="text"
                                                placeholder="Select time"
                                                class="timepicker border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Type (fixed width, non-stretch) -->
                                        <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-type">Type</label>
                                            <div x-data="{ selected: 'store', open: false }" class="relative w-full">
                                                <button id="delivery-type" type="button" @click="open = !open"
                                                    class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none">
                                                    <template x-if="selected === 'store'">
                                                        <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                    <template x-if="selected === 'truck'">
                                                        <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                    <ul>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'store'; open = false"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'truck'; open = false"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="hidden" name="type" :value="selected">
                                            </div>
                                        </div>
                                        <!-- Status -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-status">Status</label>
                                            <select id="delivery-status" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="Pending" selected>Pending</option>
                                                <option value="Completed">Completed</option>
                                                <option value="Reschedule">Reschedule</option>
                                            </select>
                                        </div>
                                        <!-- Location -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-location">Location</label>
                                            <select id="delivery-location" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option selected>Bon Aqua</option>
                                            </select>
                                        </div>
                                        <!-- Technician -->
                                        <div class="flex flex-col items-start min-w-[90px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="delivery-technician">Technician</label>
                                            <select id="delivery-technician" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option selected>Select Technician</option>
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
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-date">Date</label>
                                            <input id="return-date" type="text"
                                                data-format="{{ config('app.date.js_date_format') }}"
                                                placeholder="Select date"
                                                class="datepicker border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Time -->
                                        <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-time">Time</label>
                                            <input id="return-time" type="text"
                                                placeholder="Select time"
                                                class="timepicker border rounded px-1.5 py-1 text-xs w-full" />
                                        </div>
                                        <!-- Type (fixed width, non-stretch) -->
                                        <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-type">Type</label>
                                            <div x-data="{ selected: 'store', open: false }" class="relative w-full">
                                                <button id="return-type" type="button" @click="open = !open"
                                                    class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none">
                                                    <template x-if="selected === 'store'">
                                                        <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                    <template x-if="selected === 'truck'">
                                                        <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                    </template>
                                                </button>
                                                <div x-show="open" @click.away="open = false"
                                                    class="absolute z-10 mt-1 w-full bg-white border rounded shadow-lg">
                                                    <ul>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'store'; open = false"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-building-storefront class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                        <li>
                                                            <button type="button"
                                                                @click="selected = 'truck'; open = false"
                                                                class="w-full flex items-center justify-center px-2 py-1 hover:bg-yellow-100">
                                                                <x-heroicon-o-truck class="w-4 h-4 text-yellow-600" />
                                                            </button>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <input type="hidden" name="type" :value="selected">
                                            </div>
                                        </div>
                                        <!-- Status -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-status">Status</label>
                                            <select id="return-status" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option value="Pending" selected>Pending</option>
                                                <option value="Completed">Completed</option>
                                                <option value="Reschedule">Reschedule</option>
                                            </select>
                                        </div>
                                        <!-- Location -->
                                        <div class="flex flex-col items-start min-w-[70px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-location">Location</label>
                                            <select id="return-location" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option selected>Bon Aqua</option>
                                            </select>
                                        </div>
                                        <!-- Technician -->
                                        <div class="flex flex-col items-start min-w-[90px] flex-1">
                                            <label class="block text-xs font-medium text-gray-500 mb-0.5" for="return-technician">Technician</label>
                                            <select id="return-technician" class="border rounded px-1.5 py-1 text-xs w-full">
                                                <option selected>Select Technician</option>
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
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                                        </div>
                                    </div>
                                    <!-- Machine Hours -->
                                    <div class="flex flex-col items-center">
                                        <div>Machine Hours</div>
                                        <div class="flex justify-center gap-2 mb-1">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
                                        </div>
                                    </div>
                                    <!-- Video -->
                                    <div class="flex flex-col items-center">
                                        <div>Video</div>
                                        <div class="flex justify-center gap-2 mb-1">
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-xs font-bold">D</span>
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-xs font-bold">R</span>
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
                <label class="block text-sm font-semibold text-gray-700 mb-1">Order Notes:</label>
                <input type="text" value="{{ $order->order_note }}"
                    class="w-full border rounded px-3 py-2 text-sm text-gray-800" />
                <div class="flex justify-end">
                    <button class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                        Save Note
                    </button>
                </div>
            </div>
        </div>
    </div>


@endsection

@push('js')

@endpush
