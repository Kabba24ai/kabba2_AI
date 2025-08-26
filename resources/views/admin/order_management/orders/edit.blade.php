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
                @if ($order->last_payment_status === 'Pending')
                    <button id="pendingPaymentBtn"
                        class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                        PENDING PAYMENT
                    </button>
                    @if ($order->last_payment_type !== 'Card')
                        <button id="addToAccountBtn"
                            class="px-4 py-1 text-xs font-semibold bg-green-600 text-white rounded-full hover:bg-green-700">
                            Add to Account
                        </button>
                        {{-- <button id="confirmPaymentBtn"
                            class="px-4 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700">
                            Confirm payment
                        </button> --}}
                    @endif
                @elseif ($order->last_payment_status === 'Paid')
                    <span
                        class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                        Paid In Full Via -
                        {{ $order->last_payment_type === 'Card' ? 'Credit/Debit Card' : $order->last_payment_type }}
                    </span>
                    <button
                        class="flex items-center px-3 py-1 text-xs font-semibold bg-gray-100 text-gray-800 rounded hover:bg-gray-200 transition rounded-lg">
                        <x-heroicon-o-credit-card class="w-4 h-4 mr-1 text-gray-600" />
                        Refund
                    </button>
                @elseif ($order->last_payment_status === 'Failed')
                    <span
                        class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-red-500 text-white rounded-full">
                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                        PAYMENT FAILED
                    </span>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap gap-2">
                <div class="relative group inline-block">
                    <button id="reorderBtn" type="button"
                        class="inline-flex items-center px-3 py-1.5 text-sm bg-orange-500 text-white rounded hover:bg-orange-600 focus:outline-none">
                        <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4 mr-1" /> Reorder
                    </button>
                    <!-- Reorder Modal -->
                    <div id="reorderModal"
                        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
                        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
                            <!-- Header -->
                            <div class="flex justify-between items-center p-4 border-b">
                                <h2 class="text-lg font-semibold">Reorder</h2>
                                <button type="button"
                                    class="close-reorder-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
                            </div>
                            <!-- Body -->
                            <form id="reorderForm" class="flex-1 flex flex-col justify-between">
                                <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                                    <div>
                                        <label class="text-sm font-medium text-gray-700 required">Order Type</label>
                                        <select id="orderTypeSelect" name="order_type"
                                            class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700"
                                            required>
                                            <option value="new">New Order</option>
                                            <option value="duplicate">Duplicate Order</option>
                                        </select>
                                    </div>
                                    <div id="duplicateOrderSection" class="hidden">
                                        <label class="text-sm font-medium text-gray-700 mb-2">Products</label>
                                        <div class="space-y-2">
                                            @foreach ($order->products as $orderProduct)
                                                @if (!empty($orderProduct->product))
                                                    <div class="flex items-center gap-2">
                                                        <span class="flex-1">{{ $orderProduct->product_name }}</span>
                                                        <input type="text"
                                                            name="delivery_dates[{{ $orderProduct->product->unique_id }}]"
                                                            data-format="{{ config('app.date.js_date_format') }}"
                                                            placeholder="Select date"
                                                            data-min-date="{{ now()->format(config('app.date.db_date_format')) }}"
                                                            class="reorder-datepicker border rounded px-2 py-2 text-xs" />
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                <!-- Footer -->
                                <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                                    <button type="button"
                                        class="close-reorder-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                                        Cancel
                                    </button>
                                    <button type="submit"
                                        class="px-6 py-2 rounded-md bg-orange-600 text-white font-medium hover:bg-orange-700 shadow-sm transition">
                                        Continue
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-500 text-white rounded hover:bg-blue-600">
                    <x-heroicon-o-envelope class="w-4 h-4 mr-1" /> Email Invoice
                </button>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                    <x-heroicon-o-printer class="w-4 h-4 mr-1" /> Print Invoice
                </button>
                <button class="hidden items-center px-3 py-1.5 text-sm bg-green-500 text-white rounded hover:bg-green-600 ">
                    <x-heroicon-o-check class="w-4 h-4 mr-1" /> Save
                </button>
            </div>
        </div>

        {{-- Sub-links under customer --}}
        <div class="mt-2 flex gap-4 text-sm text-blue-600">
            <a href="{{ route('admin.crm.customers.view', $order->customer?->unique_id) }}" target="_blank"
                class="inline-flex items-center hover:underline {{ !$order->customer ? 'pointer-events-none opacity-50 cursor-not-allowed' : '' }}"
                @if (!$order->customer) tabindex="-1" aria-disabled="true" @endif>
                <x-heroicon-o-user class="w-4 h-4 mr-1" /> Customer Details
            </a>
            <form action="{{ route('admin.crm.customers.impersonate-login', $order->customer?->unique_id) }}" method="POST"
                target="_blank" class="inline-flex items-center">
                @csrf
                <button type="submit" class="inline-flex items-center hover:underline">
                    <x-heroicon-o-link class="w-4 h-4 mr-1" /> Website Login
                </button>
            </form>
            @if ($order->reference_order_number)
                <a href="{{ $order->referenceOrder ? route('admin.order-management.orders.edit', $order->referenceOrder->unique_id) : 'javascript:void(0);' }}"
                    target="_blank"
                    class="inline-flex items-center hover:underline {{ !$order->referenceOrder ? 'pointer-events-none opacity-50 cursor-not-allowed' : '' }}"
                    @if (!$order->referenceOrder) tabindex="-1" aria-disabled="true" @endif>
                    <x-heroicon-o-arrow-path-rounded-square class="w-4 h-4 mr-1" /> Reference Order:
                    {{ $order->reference_order_number }}
                </a>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

        {{-- Billing Info --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Billing Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer"
                        id="editBillingBtn" data-billing="@json($order->billingAddress ?? [])" />
                </div>
            </div>
            <div class="p-6">
                <div class="text-sm text-gray-700 space-y-2 billing-address-section">
                    <input type="hidden" id="billing_first_name_input"
                        data-first-name="{{ $order->billingAddress->first_name ?? '' }}">
                    <input type="hidden" id="billing_last_name_input"
                        data-last-name="{{ $order->billingAddress->last_name ?? '' }}">
                    <input type="hidden" id="billing_email_input" data-email="{{ $order->billingAddress->email ?? '' }}">
                    <input type="hidden" id="billing_phone_input" data-phone="{{ $order->billingAddress->phone ?? '' }}">
                    <input type="hidden" id="billing_address_input"
                        data-address="{{ $order->billingAddress->address ?? '' }}">
                    <input type="hidden" id="billing_state_input"
                        data-state="{{ $order->billingAddress->state_id ?? '' }}">
                    <input type="hidden" id="billing_city_input" data-city="{{ $order->billingAddress->city ?? '' }}">
                    <input type="hidden" id="billing_zip_code_input"
                        data-zip-code="{{ $order->billingAddress->zip_code ?? '' }}">
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Customer Name:</span>
                        <span class="col-span-2 text-right"
                            id="billing_name">{{ $order->billingAddress->full_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Email:</span>
                        <span class="col-span-2 text-right" id="billing_email">
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
                        <span class="col-span-2 text-right"
                            id="billing_phone">{{ $order->billingAddress->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Billing Address:</span>
                        <span class="col-span-2 text-right" id="billing_address">
                            @if ($order->billingAddress && $order->billingAddress->full_address)
                                {{ $order->billingAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    @if ($order->billingAddress && $order->billingAddress->full_address)
                        <div class="grid grid-cols-3" id="billing_map_link">
                            <span></span>
                            <a href="https://maps.google.com/?q={{ urlencode($order->billingAddress->full_address) }}"
                                target="_blank" class="text-blue-600 text-xs hover:underline col-span-2 text-right">See on
                                maps</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Shipping Info --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                    <x-heroicon-o-pencil-square class="w-5 h-5 text-blue-500 hover:text-blue-600 cursor-pointer"
                        id="editShippingBtn" />
                </div>
            </div>
            <div class="p-6">
                <input type="hidden" id="shipping_first_name_input"
                    data-first-name="{{ $order->shippingAddress->first_name ?? '' }}">
                <input type="hidden" id="shipping_last_name_input"
                    data-last-name="{{ $order->shippingAddress->last_name ?? '' }}">
                <input type="hidden" id="shipping_email_input" data-email="{{ $order->shippingAddress->email ?? '' }}">
                <input type="hidden" id="shipping_phone_input" data-phone="{{ $order->shippingAddress->phone ?? '' }}">
                <input type="hidden" id="shipping_address_input"
                    data-address="{{ $order->shippingAddress->address ?? '' }}">
                <input type="hidden" id="shipping_state_input"
                    data-state="{{ $order->shippingAddress->state_id ?? '' }}">
                <input type="hidden" id="shipping_city_input" data-city="{{ $order->shippingAddress->city ?? '' }}">
                <input type="hidden" id="shipping_zip_code_input"
                    data-zip-code="{{ $order->shippingAddress->zip_code ?? '' }}">

                <div
                    class="text-sm text-gray-700 space-y-1 shipping-address-section {{ $order->shippingAddress->isSameAs($order->billingAddress) ? 'hidden' : '' }}">
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Customer Name:</span>
                        <span class="col-span-2 text-right"
                            id="shipping_name">{{ $order->shippingAddress->full_name ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Email:</span>
                        <span class="col-span-2 text-right" id="shipping_email">
                            @if ($order->shippingAddress->email ?? false)
                                <a href="mailto:{{ $order->shippingAddress->email }}"
                                    class="text-blue-600 hover:underline">{{ $order->shippingAddress->email }}</a>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Phone:</span>
                        <span class="col-span-2 text-right"
                            id="shipping_phone">{{ $order->shippingAddress->phone ?? 'N/A' }}</span>
                    </div>
                    <div class="grid grid-cols-3">
                        <span class="font-medium">Delivery Address:</span>
                        <span class="col-span-2 text-right" id="shipping_address">
                            @if ($order->shippingAddress && $order->shippingAddress->full_address)
                                {{ $order->shippingAddress->full_address }}
                            @else
                                N/A
                            @endif
                        </span>
                    </div>
                    @if ($order->shippingAddress && $order->shippingAddress->full_address)
                        <div class="grid grid-cols-3" id="shipping_map_link">
                            <span></span>
                            <a href="https://maps.google.com/?q={{ urlencode($order->shippingAddress->full_address) }}"
                                target="_blank" class="text-blue-600 text-xs hover:underline col-span-2 text-right">See on
                                maps</a>
                        </div>
                    @endif
                </div>
                <p class="text-sm text-blue-600 shipping-address-section {{ $order->shippingAddress->isSameAs($order->billingAddress) ? '' : 'hidden' }}"
                    id="shipping_same_as_billing">Same as Billing</p>

            </div>
        </div>

        {{-- History --}}
        <div class="bg-white rounded-lg border border-gray-200">
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">History</h3>
                </div>
            </div>
            <div class="p-6 max-h-60 overflow-y-auto">
                <ul class="list-disc text-sm text-gray-700 space-y-1 pl-3 ">
                    @forelse ($order->history as $history)
                        <li>
                            <span>
                                {{ $history->description }}

                                <br>
                                <span class="text-xs text-gray-500">
                                    {{ \App\Helpers\CustomHelper::formatDateTime($history->created_at) }}
                                </span>
                            </span>
                        </li>
                    @empty
                        <li class="text-gray-400 text-sm">No history available.</li>
                    @endforelse
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
                            @if ($orderProduct?->product?->image_url)
                                <img src="{{ $orderProduct->product->image_url }}"
                                    alt="{{ $orderProduct->product_name }}"
                                    class="object-contain w-full h-full rounded" />
                            @else
                                Image not available
                            @endif
                        </div>
                        <div>
                            @php
                                if ($orderProduct?->product) {
                                    $href = route(
                                        'admin.product-management.products.edit',
                                        $orderProduct->product->unique_id,
                                    );
                                }
                            @endphp
                            <a href="{{ $href ?? 'javascript:void(0);' }}"
                                class="text-blue-600 font-semibold hover:underline">
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

                        {{-- @if (!empty($orderProduct->service_method))
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
                        @endif --}}

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



                        @if ($orderProduct->product_data && count($orderProduct->product_data))
                            <div class="flex justify-between">
                                <span class="underline">Options</span>
                            </div>
                            <ul class="pl-5 list-disc text-gray-600 text-sm">
                                @foreach ($orderProduct->product_data['product_rental_items_prices'] as $rentalKey => $rentalPrice)
                                    <li class="flex justify-between">
                                        <span>
                                            {{ collect(\App\Enums\Products\ProductCustomStaticLabel::cases())->firstWhere('name', $rentalKey)?->value ?? ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
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
                            @if ($orderProduct->product_data['product_type'] === 'Rental')
                                <div class="flex items-center justify-between">
                                </div>
                                {{-- Delivery Schedule --}}
                                <div class="space-y-2 mb-4">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                        <x-heroicon-s-truck class="w-5 h-5 text-red-500 mr-1" /> Delivery Schedule
                                    </div>
                                    <div class="bg-white border rounded-xl p-3 flex flex-wrap gap-3 items-center">
                                        <div class="flex flex-wrap items-center gap-2 w-full">
                                            <!-- Date -->
                                            <div class="flex flex-col items-start min-w-[100px] max-w-[120px] flex-[0.9]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_date_{{ $orderProduct->unique_id }}">Date</label>
                                                <input type="text" id="delivery_date_{{ $orderProduct->unique_id }}"
                                                    data-format="{{ config('app.date.js_date_format') }}"
                                                    value="{{ $orderProduct->delivery_date ? \App\Helpers\CustomHelper::formatDate($orderProduct->delivery_date) : '' }}"
                                                    placeholder="Select date"
                                                    data-min-date="{{ now()->format(config('app.date.db_date_format')) }}"
                                                    class="datepicker delivery_date border rounded px-1.5 py-1 text-xs w-full" />
                                            </div>
                                            <!-- Time -->
                                            <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_time_{{ $orderProduct->unique_id }}">Time</label>
                                                <input type="text" placeholder="Select time"
                                                    id="delivery_time_{{ $orderProduct->unique_id }}"
                                                    value="{{ $orderProduct->delivery_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->delivery_time) : '' }}"
                                                    class="delivery_time border rounded px-1.5 py-1 text-xs w-full" />
                                            </div>
                                            <!-- Type (fixed width, non-stretch) -->
                                            <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="delivery_transport_mode-btn-{{ $orderProduct->unique_id }}">Type</label>
                                                <div id="delivery_transport_mode-{{ $orderProduct->unique_id }}"
                                                    x-data="{ selected: '{{ $orderProduct->delivery_transport_mode ?? 'Store' }}', open: false }" class="relative w-full">
                                                    <button
                                                        id="delivery_transport_mode-btn-{{ $orderProduct->unique_id }}"
                                                        type="button" @click="open = !open"
                                                        class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none delivery_transport_mode">
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
                                                    <input type="hidden" name="type" class="delivery_transport_mode"
                                                        :value="selected" x-ref="deliveryTypeInput">
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
                                                    data-min-date="{{ now()->format(config('app.date.db_date_format')) }}"
                                                    class="datepicker pickup_date border rounded px-1.5 py-1 text-xs w-full" />
                                            </div>
                                            <!-- Time -->
                                            <div class="flex flex-col items-start min-w-[80px] max-w-[100px] flex-[0.8]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_time_{{ $orderProduct->unique_id }}">Time</label>
                                                <input type="text" id="pickup_time_{{ $orderProduct->unique_id }}"
                                                    placeholder="Select time"
                                                    value="{{ $orderProduct->pickup_time ? \App\Helpers\CustomHelper::formatTime($orderProduct->pickup_time) : '' }}"
                                                    class="pickup_time border rounded px-1.5 py-1 text-xs w-full" />
                                            </div>
                                            <!-- Type (fixed width, non-stretch) -->
                                            <div class="flex flex-col items-start flex-shrink-0 w-[44px]">
                                                <label class="block text-xs font-medium text-gray-500 mb-0.5"
                                                    for="pickup_transport_mode-btn-{{ $orderProduct->unique_id }}">Type</label>
                                                <div id="pickup_transport_mode_{{ $orderProduct->unique_id }}"
                                                    x-data="{ selected: '{{ $orderProduct->pickup_transport_mode ?? 'Store' }}', open: false }" class="relative w-full">
                                                    <button id="pickup_transport_mode-btn-{{ $orderProduct->unique_id }}"
                                                        type="button" @click="open = !open"
                                                        class="border rounded px-1.5 py-1 text-xs w-full flex items-center justify-center gap-1 focus:outline-none pickup_transport_mode">
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
                                                    <input type="hidden" name="type" class="pickup_transport_mode"
                                                        :value="selected" x-ref="pickupTypeInput">
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
                                                    {{-- <option value="Reschedule"
                                                        {{ ($orderProduct->pickup_status ?? '') === 'Reschedule' ? 'selected' : '' }}>
                                                        Reschedule</option> --}}
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
                                            <span
                                                class="inline-block w-2 h-2 rounded-full bg-green-500 mr-1"></span>Completed
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
                            @else
                                <div class="text-gray-500 text-sm">
                                    <p>No delivery or return schedules available for this product.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach

                {{-- Status Checklist --}}
                <div class="border rounded-xl p-4 bg-white">
                    <div class="grid grid-cols-2 gap-2 text-center text-xs font-medium text-gray-700 mb-2">
                        <!-- Terms -->
                        <div class="flex flex-col items-center">
                            <div>Terms</div>
                            <div class="flex justify-center mb-1 gap-1">
                                @if ($order->terms_status->isPending())
                                    <a href="{{ route('front.terms-and-conditions.index', $order->unique_id) }}"
                                        target="_blank" title="View Terms">
                                        <span
                                            class="inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-base font-bold">
                                            <x-heroicon-o-x-mark class="w-4 h-4" />
                                        </span>
                                    </a>
                                    <a href="{{ route('front.terms-and-conditions.index', $order->unique_id) }}"
                                        target="_blank" title="View Terms">
                                        <span
                                            class="relative group inline-flex items-center justify-center w-6 h-6 rounded bg-red-500 text-white text-base font-bold">
                                            <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />

                                            <!-- Tooltip -->
                                            <span
                                                class="absolute top-full mb-1 hidden group-hover:block px-2 py-1 bg-black text-white text-xs rounded shadow-lg whitespace-nowrap">
                                                Send terms signature request
                                            </span>
                                        </span>
                                    </a>
                                @else
                                    <a href="{{ route('front.terms-and-conditions.index', $order->unique_id) }}"
                                        target="_blank" title="View Terms">
                                        <span
                                            class="inline-flex items-center justify-center w-6 h-6 rounded bg-green-500 text-white text-base font-bold">
                                            <x-heroicon-o-check class="w-4 h-4" />
                                        </span>
                                    </a>
                                @endif
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
            <div class="bg-white rounded-lg border border-gray-200 p-4 space-y-2 shadow-sm flex flex-col relative">
                <!-- Add Note Button -->
                <button
                    class="absolute top-4 right-4 flex items-center gap-1 px-3 py-1.5 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm"
                    id="addNoteBtn">
                    <x-heroicon-o-plus class="w-4 h-4" /> Add Note
                </button>
                <div class="p-6 max-h-60 overflow-y-auto mt-10" id="noteDiv">
                    <x-admin.order-management.orders.order-notes-list :notes="$order->notes" />
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Note Modal -->
    <div id="noteModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-md shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="noteModalTitle" class="text-lg font-semibold">Add Note</h2>
                <button type="button" onclick="closeNoteModal()"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1 flex flex-col justify-between',
                    'id' => 'noteForm',
                ])->open() }}
            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="user_id">User</label>
                    {!! html()->select('user_id', $employees->pluck('full_name', 'id')->toArray())->id('user_id')->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                        ])->required() !!}
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 required" for="note_text">Note</label>
                    {!! html()->textarea('note')->id('note_text')->class(['w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500'])->attribute('rows', 5)->placeholder('Enter note...')->required() !!}
                </div>
            </div>
            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" onclick="closeNoteModal()"
                    class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            {{ html()->form()->close() }}
        </div>
    </div>

    <!-- Address Edit Modal (Reusable for Billing & Delivery) -->
    <div id="addressModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="addressModalTitle" class="text-lg font-semibold">Edit Address</h2>
                <button type="button" onclick="closeModal()"
                    class="text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'addressForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- First Name -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="first_name">First name</label>
                        {!! html()->text('first_name', old('first_name'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('first_name'),
                            ])->attributes([
                                'placeholder' => 'Enter first name',
                                'id' => 'first_name',
                                'autocomplete' => 'given-name',
                            ])->required() !!}
                    </div>
                    <!-- Last Name -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="last_name">Last name</label>
                        {!! html()->text('last_name', old('last_name'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('last_name'),
                            ])->attributes([
                                'placeholder' => 'Enter last name',
                                'id' => 'last_name',
                                'autocomplete' => 'family-name',
                            ])->required() !!}
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Email -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="email">Email</label>
                        {!! html()->email('email', old('email'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('email'),
                            ])->attributes([
                                'placeholder' => 'Enter email',
                                'id' => 'email',
                                'autocomplete' => 'email',
                                'readonly' => 'readonly',
                                'disabled' => 'disabled',
                            ])->required() !!}
                    </div>
                    <!-- Phone -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="phone">Phone</label>
                        {!! html()->text('phone', old('phone'))->class([
                                'masked-phone w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('phone'),
                            ])->attributes([
                                'placeholder' => '(xxx) xxx-xxxx',
                                'id' => 'phone',
                                'autocomplete' => 'tel',
                            ])->required() !!}
                    </div>
                    <!-- Type (hidden, set by JS) -->
                    <input type="hidden" name="type" id="type" value="{{ old('type') }}">
                </div>

                <!-- Address -->
                <div>
                    <label class="text-sm font-medium text-gray-700" for="address">Address</label>
                    {!! html()->text('address', old('address'))->class([
                            'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                            'border-red-500' => $errors->has('address'),
                        ])->attributes([
                            'placeholder' => 'Enter address',
                            'id' => 'address',
                            'autocomplete' => 'street-address',
                        ])->required() !!}
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- State -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="state">State</label>
                        {!! html()->select(
                                'state_id',
                                $states->mapWithKeys(fn($state) => [$state->id => $state->name])->toArray(),
                                old('state', $customer->state_id ?? ''),
                            )->id('state')->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500 bg-white text-gray-700',
                                'border-red-500' => $errors->has('state'),
                            ])->required() !!}
                    </div>
                    <!-- City -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="city">City</label>
                        {!! html()->text('city', old('city'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('city'),
                            ])->attributes([
                                'placeholder' => 'Enter city',
                                'id' => 'city',
                                'autocomplete' => 'address-level2',
                            ])->required() !!}
                    </div>
                    <!-- Zip Code -->
                    <div>
                        <label class="text-sm font-medium text-gray-700" for="zip_code">Zip code</label>
                        {!! html()->number('zip_code', old('zip_code'))->class([
                                'w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring focus:border-blue-500',
                                'border-red-500' => $errors->has('zip_code'),
                            ])->attributes([
                                'placeholder' => 'Enter zip code',
                                'id' => 'zip_code',
                                'autocomplete' => 'postal-code',
                            ])->required() !!}
                    </div>
                </div>

            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button" onclick="closeModal()"
                    class="px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            </form>
        </div>
    </div>

    <!-- Process Payment Modal -->
    <div id="processPaymentModal"
        class="fixed inset-0 z-[99999] hidden overflow-y-auto bg-gray-500/75 transition-opacity flex justify-center items-center">
        <div class="bg-white rounded-lg w-full max-w-lg shadow-lg flex flex-col">
            <!-- Header -->
            <div class="flex justify-between items-center p-4 border-b">
                <h2 id="addressModalTitle" class="text-lg font-semibold">Process Payment</h2>
                <button type="button"
                    class="close-process-payment-modal-btn text-2xl text-gray-400 hover:text-gray-700 leading-none focus:outline-none">&times;</button>
            </div>
            <!-- Body -->
            {{ html()->form()->attributes([
                    'data-parsley-validate' => true,
                    'class' => 'flex-1',
                    'id' => 'paymentForm',
                ])->open() }}

            <div class="overflow-y-auto flex flex-col gap-y-4 px-4 py-4">
                <!-- Payment Method -->
                <div class="space-y-4">
                    <label class="text-sm font-medium text-gray-700">Select Payment Method</label>

                    <div class="grid grid-cols-1 gap-3">
                        <!-- Cash / Manual -->
                        <label class="cursor-pointer block">
                            <input type="radio" name="payment_method" value="cash" class="hidden peer" checked>
                            <div
                                class="border rounded-lg p-4 flex items-start gap-3 transition peer-checked:border-green-400 peer-checked:bg-green-50 peer-hover:border-blue-400 peer-hover:bg-blue-50">
                                <div class="mt-1">
                                    <!-- Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Confirm Payment Received</p>
                                    <p class="text-sm text-gray-500">Mark as paid (cash, check, etc.)</p>
                                </div>
                            </div>
                        </label>

                        <!-- Credit Card -->
                        <label class="cursor-pointer block">
                            <input type="radio" name="payment_method" value="card" class="hidden peer">
                            <div
                                class="border rounded-lg p-4 flex items-start gap-3 transition peer-checked:border-blue-400 peer-checked:bg-blue-50 peer-hover:border-blue-400 peer-hover:bg-blue-50">
                                <div class="mt-1">
                                    <!-- Icon -->
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-500" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <rect x="2" y="5" width="20" height="14" rx="2" ry="2"
                                            stroke-width="2"></rect>
                                        <line x1="2" y1="10" x2="22" y2="10"
                                            stroke-width="2"></line>
                                    </svg>
                                </div>
                                <div>
                                    <p class="font-medium text-gray-900">Charge Credit Card</p>
                                    <p class="text-sm text-gray-500">Process card payment now</p>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Credit card dropdown (hidden by default) -->
                    <div id="creditCardOptions" class="hidden mt-3 ">
                        <div class="mb-4">
                            <label for="cardOption" class="block text-sm font-medium text-gray-700 mb-1 required">Card
                                Options</label>
                            <select id="cardOption" name="card_option"
                                class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                <option value="" selected>New Card</option>

                                @if ($order->customer->cards && $order->customer->cards->count() > 0)
                                    <option value="CardOnFile">Card on File</option>
                                @endif
                            </select>
                        </div>

                        <!-- New Card Fields -->
                        <div id="newCardFields" class="mb-4 hidden">
                            <div class="grid md:grid-cols-2 gap-4">
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="First name" id="firstName" name="firstName"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="Last name" id="lastName" name="lastName"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-2">
                                    <input type="text" placeholder="Card number" maxlength="19" id="cardNumber"
                                        name="cardNumber"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="MM/YY" maxlength="5" id="expiry"
                                        name="expiry"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                                <div class="md:col-span-1">
                                    <input type="text" placeholder="CVC" maxlength="4" id="cvc"
                                        name="cvc"
                                        class="border border-gray-300 rounded-md py-2 px-3 text-sm w-full" />
                                </div>
                            </div>
                            <input type="hidden" name="opaqueDataValue" id="opaqueDataValue" />
                            <input type="hidden" name="opaqueDataDescriptor" id="opaqueDataDescriptor" />
                        </div>

                        <!-- Card on File Dropdown -->
                        <div id="cardOnFileDropdown" class="mb-4 hidden">
                            @if ($order->customer->cards && $order->customer->cards->count() > 0)
                                <label class="block text-sm font-medium text-gray-700 mb-1 required">Select Existing
                                    Card</label>
                                <select name="customer_card"
                                    class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm text-gray-700">
                                    <option value="">-- Select a saved card --</option>
                                    @foreach ($order->customer->cards as $card)
                                        <option value="{{ $card->unique_id }}">{{ $card->card_number }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex justify-end gap-3 items-center px-6 py-4 border-t bg-gray-50 rounded-b-lg">
                <button type="button"
                    class="close-process-payment-modal-btn px-5 py-2 rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-100 transition">
                    Cancel
                </button>
                <button type="submit"
                    class="px-6 py-2 rounded-md bg-blue-600 text-white font-medium hover:bg-blue-700 shadow-sm transition">
                    Save
                </button>
            </div>
            </form>
        </div>
    </div>

@endsection

@push('js')
    @if ($paymentSetting['payment_test_mode'] ?? false)
        <script src="https://jstest.authorize.net/v1/Accept.js"></script>
    @else
        <script src="https://js.authorize.net/v1/Accept.js"></script>
    @endif
    <script>
        // Get the order id (replace with actual variable)
        const orderUniqueId = '{{ $order->unique_id }}';
        // Note Modal logic
        const noteModal = document.getElementById('noteModal');
        const noteForm = document.getElementById('noteForm');
        const noteText = document.getElementById('note_text');
        const userIdInput = document.getElementById('user_id');
        const addNoteBtn = document.getElementById('addNoteBtn');
        const noteModalTitle = document.getElementById('noteModalTitle');
        const noteDiv = document.getElementById('noteDiv');

        // Fetch and refresh notes list
        function fetchNotes() {
            if (!noteDiv) return;
            const fetchUrl = '{{ route('admin.order-management.orders.notes.index', [':unique_id']) }}'.replace(
                ':unique_id', orderUniqueId);
            apiFetch(fetchUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                }, {
                    loaderSelector: '#note-loading',
                    containerSelector: '#noteDiv'
                })
                .then(res => {
                    if (res && res.success) {
                        let html = '';
                        noteDiv.innerHTML = res.html;
                        //notyf.success(res.message);
                    } else {
                        notyf.error(res.message);
                    }
                })
        }

        // Delete Note
        document.addEventListener('click', function(e) {
            if (e.target.closest('button[title="Delete Note"]')) {
                const btn = e.target.closest('button');
                const noteId = btn.getAttribute('data-note-id');

                showConfirm('Do you want to delete this note?', 'Are you sure?').then((result) => {
                    if (result.isConfirmed) {
                        const url =
                            '{{ route('admin.order-management.orders.notes.delete', [':unique_id', ':noteId']) }}'
                            .replace(':unique_id', orderUniqueId)
                            .replace(':noteId', noteId);

                        apiFetch(url, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content'),
                                'Accept': 'application/json'
                            }
                        }).then(res => {
                            if (res && res.success) {
                                notyf.success(res.message);
                                fetchNotes(); // reload the notes list
                            } else {
                                notyf.error(res.message);
                            }
                        })
                    }
                });
            }
        });

        // Delegated event: Edit Note
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('button[title="Edit Note"]');
            if (!btn) return;

            // Get data from attributes
            const noteId = btn.dataset.noteId;
            const noteText = btn.dataset.noteText;
            const userId = btn.dataset.userId;

            // Populate modal fields
            document.getElementById('note_text').value = noteText;
            document.getElementById('user_id').value = userId;
            document.getElementById('noteModalTitle').textContent = 'Edit Note';

            // Add hidden input to track edit mode
            let hidden = document.getElementById('editNoteId');
            if (!hidden) {
                hidden = document.createElement('input');
                hidden.type = 'hidden';
                hidden.name = 'note_id';
                hidden.id = 'editNoteId';
                document.getElementById('noteForm').appendChild(hidden);
            }
            hidden.value = noteId;

            // Show modal
            document.getElementById('noteModal').classList.remove('hidden');
        });

        document.addEventListener('DOMContentLoaded', function() {
            window.closeNoteModal = function() {
                noteModal.classList.add('hidden');
                noteForm.reset();

                const editField = document.getElementById('editNoteId');
                if (editField) {
                    editField.remove();
                }
            }

            if (addNoteBtn) {
                addNoteBtn.addEventListener('click', function() {
                    noteModalTitle.textContent = 'Add Note';
                    noteModal.classList.remove('hidden');
                    noteText.value = '';
                    noteText.focus();
                });
            }

            // Optionally: Allow closing note modal with Esc key
            document.addEventListener('keydown', function(event) {
                if (!noteModal.classList.contains('hidden') && event.key === "Escape") {
                    closeNoteModal();
                }
            });

            // Handle note form submit (AJAX logic to be added as needed)
            noteForm.addEventListener('submit', function(e) {
                e.preventDefault();

                if (!$(noteForm).parsley().isValid()) {
                    $(noteForm).parsley().validate();
                    return;
                }

                let url;
                let method = 'POST';
                // Save note via AJAX using apiFetch
                const note = noteText.value.trim();
                const userId = userIdInput.value;
                const editField = document.getElementById('editNoteId');

                if (editField && editField.value) {
                    // Editing existing note
                    url =
                        '{{ route('admin.order-management.orders.notes.update', [':unique_id', ':note_id']) }}'
                        .replace(':unique_id', orderUniqueId)
                        .replace(':note_id', editField.value);
                    method = 'PUT';
                } else {
                    // Adding new note
                    url = '{{ route('admin.order-management.orders.notes.store', [':unique_id']) }}'
                        .replace(':unique_id', orderUniqueId);

                }

                const submitBtn = noteForm.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                apiFetch(url, {
                        method: method,
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: JSON.stringify({
                            note: note,
                            user_id: userId
                        })
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            closeNoteModal(); // Close modal after saving
                            fetchNotes();
                        } else {
                            notyf.error(res && res.message ? res.message : '');
                        }
                    })
                    .finally(() => {
                        // After a successful update
                        if (editField) {
                            editField.remove(); // Remove the hidden input
                        }
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });


            // Confirm payment button
            // const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
            // if (confirmPaymentBtn) {
            //     confirmPaymentBtn.addEventListener('click', function() {
            //         showConfirm('Do you want to confirm this payment?', 'Are you sure?').then((result) => {
            //             if (result.isConfirmed) {
            //                 confirmPaymentBtn.disabled = true;
            //                 confirmPaymentBtn.textContent = 'Processing...';
            //                 let url =
            //                     '{{ route('admin.order-management.orders.confirm-payment', ':unique_id') }}';
            //                 url = url.replace(':unique_id', orderUniqueId);
            //                 apiFetch(url, {
            //                         method: 'POST',
            //                         headers: {
            //                             'Content-Type': 'application/json',
            //                             'X-CSRF-TOKEN': document.querySelector(
            //                                 'meta[name="csrf-token"]').getAttribute(
            //                                 'content')
            //                         },
            //                         body: JSON.stringify({
            //                             _method: 'PUT'
            //                         })
            //                     })
            //                     .then(res => {
            //                         if (res && res.success) {
            //                             notyf.success('Payment confirmed!');
            //                             setTimeout(() => window.location.reload(), 800);
            //                         } else {
            //                             notyf.error(res && res.message ? res.message :
            //                                 'Failed to confirm payment.');
            //                         }
            //                     })
            //                     .catch(() => {
            //                         notyf.error('Failed to confirm payment.');
            //                     })
            //                     .finally(() => {
            //                         confirmPaymentBtn.disabled = false;
            //                         confirmPaymentBtn.textContent = 'Confirm payment';
            //                     });
            //             }
            //         });
            //     });
            // }


            // Add To Account button
            const addToAccountBtn = document.getElementById('addToAccountBtn');
            if (addToAccountBtn) {
                addToAccountBtn.addEventListener('click', function() {
                    showConfirm('Do you want to add this to the account?', 'Are you sure?').then((
                        result) => {
                        if (result.isConfirmed) {
                            addToAccountBtn.disabled = true;
                            addToAccountBtn.textContent = 'Processing...';
                            let url =
                                '{{ route('admin.order-management.orders.add-to-account', ':unique_id') }}';
                            url = url.replace(':unique_id', orderUniqueId);

                            apiFetch(url, {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector(
                                            'meta[name="csrf-token"]').getAttribute(
                                            'content')
                                    },
                                    body: JSON.stringify({
                                        _method: 'PUT'
                                    })
                                })
                                .then(res => {
                                    if (res && res.success) {
                                        notyf.success('Added to account successfully!');
                                        setTimeout(() => window.location.reload(), 800);
                                    } else {
                                        notyf.error(res && res.message ? res.message :
                                            'Failed to add to account.');
                                    }
                                })
                                .catch(() => {
                                    notyf.error('Failed to add to account.');
                                })
                                .finally(() => {
                                    addToAccountBtn.disabled = false;
                                    addToAccountBtn.textContent = 'Add to Account';
                                });
                        }
                    });
                });
            }


            // const saveBtn = document.getElementById('saveNoteBtn');
            // const noteInput = document.getElementById('order_note');

            // saveBtn.addEventListener('click', function() {
            //     const note = noteInput.value;

            //     // UI: Disable and show saving...
            //     saveBtn.disabled = true;
            //     const originalText = saveBtn.textContent;
            //     saveBtn.textContent = 'Saving...';

            //     url = '{{ route('admin.order-management.orders.update-note', ':unique_id') }}';
            //     url = url.replace(':unique_id', orderUniqueId);

            //     let data = {
            //         order_note: note,
            //         _method: 'PUT'
            //     };

            //     apiFetch(url, {
            //             method: 'POST',
            //             headers: {
            //                 'Content-Type': 'application/json',
            //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
            //                     .getAttribute('content')
            //             },
            //             body: JSON.stringify(data)
            //         })
            //         .then(resData => {
            //             notyf.success('Order note updated successfully!');
            //         })
            //         .finally(() => {
            //             saveBtn.disabled = false;
            //             saveBtn.textContent = originalText;
            //         });
            // });


            document.querySelectorAll('.order-product-unique-id').forEach(function(hiddenInput, idx) {
                const container = hiddenInput.closest('.bg-white.rounded-lg.border');
                if (!container) return;

                const orderProductId = hiddenInput.value;

                // Delivery fields
                const deliveryDate = container.querySelector('.delivery_date');
                const deliveryTime = container.querySelector('.delivery_time');
                const deliveryType = container.querySelector('input[type=hidden].delivery_transport_mode');
                const deliveryStatus = container.querySelector('.delivery_status');
                const deliveryLocation = container.querySelector('.delivery_store_id');
                const deliveryTechnician = container.querySelector('.delivery_by');

                // Return fields
                const returnDate = container.querySelector('.pickup_date');
                const returnTime = container.querySelector('.pickup_time');
                const returnType = container.querySelector('input[type=hidden].pickup_transport_mode');
                const returnStatus = container.querySelector('.pickup_status');
                const returnLocation = container.querySelector('.pickup_store_id');
                const returnTechnician = container.querySelector('.pickup_by');

                // Helper to update only the changed field for delivery/return
                function updateScheduleField(type, field, value) {
                    let url =
                        '{{ route('admin.order-management.orders.update-product-schedule', [':order_unique_id', ':product_unique_id']) }}';
                    url = url.replace(':order_unique_id', orderUniqueId).replace(':product_unique_id',
                        orderProductId);

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
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: JSON.stringify(data)
                        })
                        .then(res => {
                            notyf.success(res && res.message ? res.message : 'Schedule updated!');
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
                    // Try to parse the initial value as a Date (if it exists)
                    let lastValue = deliveryTime.value ?
                        flatpickr.parseDate(deliveryTime.value, "h:i K") :
                        null;

                    flatpickr(deliveryTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K",
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse dateStr to a Date object (or null)
                            const newValue = dateStr ?
                                flatpickr.parseDate(dateStr, "h:i K") :
                                null;

                            // Only call if both are valid dates and times are different
                            // Or if lastValue was null but now we have a value
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue
                                    .getTime()) ||
                                (!lastValue && newValue)
                            );

                            if (changed) {
                                updateScheduleField('delivery', 'delivery_time', dateStr);
                                lastValue = newValue;
                            }
                        }
                    });
                }
                if (deliveryType) {
                    deliveryType.addEventListener('change', function() {
                        console.log('Delivery type changed to', deliveryType.value);
                        updateScheduleField('delivery', 'delivery_transport_mode', deliveryType
                            .value);
                    });
                }
                if (deliveryStatus) {
                    deliveryStatus.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_status', deliveryStatus.value);
                    });
                }
                if (deliveryLocation) {
                    deliveryLocation.addEventListener('change', function() {
                        updateScheduleField('delivery', 'delivery_store_id', deliveryLocation
                            .value);
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
                    // Store the initial value as a Date object (if possible)
                    let lastValue = returnTime.value ?
                        flatpickr.parseDate(returnTime.value, "h:i K") :
                        null;

                    flatpickr(returnTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K", // 12-hour format
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse the new value as a Date object
                            const newValue = dateStr ?
                                flatpickr.parseDate(dateStr, "h:i K") :
                                null;

                            // Only trigger if the date/time actually changed
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue
                                    .getTime()) ||
                                (!lastValue && newValue)
                            );

                            if (changed) {
                                updateScheduleField('return', 'pickup_time', dateStr);
                                lastValue = newValue; // Save new value for next comparison
                            }
                        }
                    });
                }
                if (returnType) {
                    returnType.addEventListener('change', function() {
                        updateScheduleField('return', 'pickup_transport_mode', returnType.value);
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

            // Keep track of what type of address we're editing
            let currentEditType = null; // 'Billing' or 'Shipping'
            let currentAddressData = {}; // will hold current address info

            // Open modal and fill data
            function openAddressModal(type, addressData = {}) {
                currentEditType = type; // "Billing" or "Shipping"
                currentAddressData = addressData || {};

                // Set the modal title
                document.getElementById('addressModalTitle').textContent =
                    `Edit ${(type == 'Billing' ? 'Billing' : 'Delivery')} Address`;

                // Set the type select field
                document.getElementById('type').value = type;

                // Fill the fields
                document.getElementById('first_name').value = addressData.first_name || '';
                document.getElementById('last_name').value = addressData.last_name || '';
                document.getElementById('email').value = addressData.email || '';
                document.getElementById('phone').value = addressData.phone || '';
                document.getElementById('address').value = addressData.address || '';
                document.getElementById('state').value = addressData.state || '';
                document.getElementById('city').value = addressData.city || '';
                document.getElementById('zip_code').value = addressData.zip_code || '';

                // Show modal
                document.getElementById('addressModal').classList.remove('hidden');
            }

            window.closeModal = function() {
                document.getElementById('addressModal').classList.add('hidden');
            }

            // Hook up the edit buttons (set IDs on your edit icons!)
            document.getElementById('editBillingBtn').addEventListener('click', function() {
                // Get current values from the billing info section
                const billingData = {
                    first_name: document.getElementById('billing_first_name_input')?.dataset
                        .firstName || '',
                    last_name: document.getElementById('billing_last_name_input')?.dataset.lastName ||
                        '',
                    email: document.getElementById('billing_email_input')?.dataset.email || '',
                    phone: document.getElementById('billing_phone_input')?.dataset.phone || '',
                    address: document.getElementById('billing_address_input')?.dataset.address || '',
                    state: document.getElementById('billing_state_input')?.dataset.state || '',
                    city: document.getElementById('billing_city_input')?.dataset.city || '',
                    zip_code: document.getElementById('billing_zip_code_input')?.dataset.zipCode || ''
                };
                openAddressModal('Billing', billingData);
            });
            document.getElementById('editShippingBtn').addEventListener('click', function() {
                // Get current values from the shipping info section
                const shippingData = {
                    first_name: document.getElementById('shipping_first_name_input')?.dataset
                        .firstName || '',
                    last_name: document.getElementById('shipping_last_name_input')?.dataset.lastName ||
                        '',
                    email: document.getElementById('shipping_email_input')?.dataset.email || '',
                    phone: document.getElementById('shipping_phone_input')?.dataset.phone || '',
                    address: document.getElementById('shipping_address_input')?.dataset.address || '',
                    state: document.getElementById('shipping_state_input')?.dataset.state || '',
                    city: document.getElementById('shipping_city_input')?.dataset.city || '',
                    zip_code: document.getElementById('shipping_zip_code_input')?.dataset.zipCode || ''
                };
                openAddressModal('Shipping', shippingData);
            });

            // Form submit

            document.getElementById('addressForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;

                if (!$(form).parsley().isValid()) {
                    $(form).parsley().validate();
                    return;
                }
                const formData = new FormData(form);
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';

                // Ensure type field matches currentEditType
                formData.set('type', currentEditType);

                // Find selected state name and add to formData
                const stateSelect = form.querySelector('#state');
                if (stateSelect) {
                    const selectedStateName = stateSelect.options[stateSelect.selectedIndex].text;
                    formData.set('state', selectedStateName);
                }

                // Unified endpoint
                const endpoint =
                    '{{ route('admin.order-management.orders.update-address', ['unique_id' => $order->unique_id]) }}';

                apiFetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res && res.success) {
                            notyf.success(res.message);
                            closeModal();
                            const addressData = res.data?.address || {};

                            if (res.data?.same_as_billing) {
                                document.querySelectorAll('.shipping-address-section').forEach(el => el
                                    .classList.add('hidden'));
                                document.getElementById('shipping_same_as_billing').classList.remove(
                                    'hidden');
                            } else {
                                document.querySelectorAll('.shipping-address-section').forEach(el => el
                                    .classList.remove('hidden'));
                                document.getElementById('shipping_same_as_billing').classList.add(
                                    'hidden');
                            }
                            // Update the address section in the UI without reload
                            if (currentEditType === 'Billing') {

                                document.getElementById('billing_first_name_input').dataset.firstName =
                                    addressData.first_name || '';
                                document.getElementById('billing_last_name_input').dataset.lastName =
                                    addressData.last_name || '';
                                document.getElementById('billing_email_input').dataset.email =
                                    addressData.email || '';
                                document.getElementById('billing_phone_input').dataset.phone =
                                    addressData.phone || '';
                                document.getElementById('billing_address_input').dataset.address =
                                    addressData.address || '';
                                document.getElementById('billing_state_input').dataset.state =
                                    addressData.state_id || '';
                                document.getElementById('billing_city_input').dataset.city = addressData
                                    .city || '';
                                document.getElementById('billing_zip_code_input').dataset.zipCode =
                                    addressData.zip_code || '';

                                document.getElementById('billing_name').textContent = addressData
                                    .full_name || '';
                                document.getElementById('billing_email').innerHTML = addressData.email ?
                                    `<a href="mailto:${addressData.email}" class="text-blue-600 hover:underline">${addressData.email}</a>` :
                                    '<span class="text-gray-500">N/A</span>';
                                document.getElementById('billing_phone').textContent = addressData
                                    .phone || '';
                                document.getElementById('billing_address').textContent = addressData
                                    .full_address || '';
                                const mapLink = document.getElementById('billing_map_link')
                                    ?.querySelector('a');
                                if (mapLink && addressData.full_address) {
                                    mapLink.href =
                                        `https://maps.google.com/?q=${encodeURIComponent(addressData.full_address)}`;
                                    mapLink.classList.remove('hidden');
                                } else if (mapLink) {
                                    mapLink.href = '#';
                                    mapLink.classList.add('hidden');
                                }
                            } else if (currentEditType === 'Shipping') {
                                document.getElementById('shipping_name').textContent = addressData
                                    .full_name || '';
                                document.getElementById('shipping_email').innerHTML = addressData
                                    .email ?
                                    `<a href="mailto:${addressData.email}" class="text-blue-600 hover:underline">${addressData.email}</a>` :
                                    '<span class="text-gray-500">N/A</span>';
                                document.getElementById('shipping_phone').textContent = addressData
                                    .phone || '';
                                document.getElementById('shipping_address').textContent = addressData
                                    .full_address || '';
                                const mapLink = document.getElementById('shipping_map_link')
                                    ?.querySelector('a');
                                if (mapLink && addressData.full_address) {
                                    mapLink.href =
                                        `https://maps.google.com/?q=${encodeURIComponent(addressData.full_address)}`;
                                    mapLink.classList.remove('hidden');
                                } else if (mapLink) {
                                    mapLink.href = '#';
                                    mapLink.classList.add('hidden');
                                }

                                document.getElementById('shipping_first_name_input').dataset.firstName =
                                    addressData.first_name || '';
                                document.getElementById('shipping_last_name_input').dataset.lastName =
                                    addressData.last_name || '';
                                document.getElementById('shipping_email_input').dataset.email =
                                    addressData.email || '';
                                document.getElementById('shipping_phone_input').dataset.phone =
                                    addressData.phone || '';
                                document.getElementById('shipping_address_input').dataset.address =
                                    addressData.address || '';
                                document.getElementById('shipping_state_input').dataset.state =
                                    addressData.state_id || '';
                                document.getElementById('shipping_city_input').dataset.city =
                                    addressData.city || '';
                                document.getElementById('shipping_zip_code_input').dataset.zipCode =
                                    addressData.zip_code || '';
                            }
                        } else {
                            notyf.error(res && res.message);
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            // Optionally: Allow closing modal with Esc key
            document.addEventListener('keydown', function(event) {
                if (event.key === "Escape") {
                    closeModal();
                }
            });

            // Modal open/close logic
            const reorderBtn = document.getElementById('reorderBtn');
            const reorderModal = document.getElementById('reorderModal');
            const orderTypeSelect = document.getElementById('orderTypeSelect');
            const duplicateOrderSection = document.getElementById('duplicateOrderSection');

            function openReorderModal() {
                orderTypeSelect.value = 'new';
                orderTypeSelect.dispatchEvent(new Event('change'));
                document.querySelectorAll('.reorder-datepicker').forEach(el => {
                    el.value = '';
                    if (el._airDatepicker) {
                        el._airDatepicker.clear();
                    }
                });
                reorderModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeReorderModal() {
                reorderModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }
            if (reorderBtn) {
                reorderBtn.addEventListener('click', openReorderModal);
            }

            // ✅ attach to BOTH close buttons
            document.querySelectorAll('.close-reorder-modal-btn').forEach(btn => btn.addEventListener('click',
                closeReorderModal));

            if (orderTypeSelect) {
                orderTypeSelect.addEventListener('change', function() {
                    if (this.value === 'duplicate') {
                        duplicateOrderSection.classList.remove('hidden');
                    } else {
                        duplicateOrderSection.classList.add('hidden');
                    }
                });
                // On load, ensure correct section is shown
                if (orderTypeSelect.value === 'duplicate') {
                    duplicateOrderSection.classList.remove('hidden');
                }
            }

            document.querySelectorAll('.reorder-datepicker').forEach(el => {
                // Only initialize if the input is visible (not .hidden)
                if (!el.classList.contains('hidden')) {
                    el._airDatepicker = new AirDatepicker(el, {
                        locale: window.airDatepickerLocaleEn,
                        timepicker: false,
                        dateFormat: el.dataset.format || window.APP_DATE_FORMAT ||
                            'yyyy-MM-dd HH:mm',
                        minDate: el.dataset.minDate ? new Date(el.dataset.minDate) : false,
                        autoClose: true,
                        keyboardNav: true,
                        // 🔹 Put the calendar in <body> so it’s not clipped or stuck
                        container: "#reorderModal",
                        // 🔹 Give it a stacking level above your modal overlay
                        zIndex: 99999
                    });
                }
            });


            document.getElementById('reorderForm').addEventListener('submit', async function(e) {
                e.preventDefault();

                const formData = new FormData(this);
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.disabled = true;
                submitBtn.textContent = 'Saving...';
                // Unified endpoint
                const endpoint =
                    '{{ route('admin.order-management.orders.reorder', ['unique_id' => $order->unique_id]) }}';

                const response = apiFetch(endpoint, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                .getAttribute('content')
                        },
                        body: formData
                    })
                    .then(res => {
                        if (res && res.success) {
                            setTimeout(() => {
                                notyf.success(res.message);
                            }, 500);
                            window.open(res.redirect_url, '_blank');
                            closeReorderModal();

                        } else {
                            notyf.error(res && res.message);
                        }
                    })
                    .finally(() => {
                        submitBtn.disabled = false;
                        submitBtn.textContent = originalText;
                    });
            });

            // Modal open/close logic
            const pendingPaymentBtn = document.getElementById('pendingPaymentBtn');
            const processPaymentModal = document.getElementById('processPaymentModal');

            function openProcessPaymentModal() {
                processPaymentModal.classList.remove('hidden');
                document.body.classList.add('overflow-hidden');
            }

            function closeProcessPaymentModal() {
                processPaymentModal.classList.add('hidden');
                document.body.classList.remove('overflow-hidden');
            }

            // 👉 Open the modal when clicking the Pending Payment pill
            if (pendingPaymentBtn) {
                pendingPaymentBtn.addEventListener('click', openProcessPaymentModal);
            }

            // 👉 Close on Escape while open
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && !processPaymentModal.classList.contains('hidden')) {
                    closeProcessPaymentModal();
                }
            });

            // 👉 (Optional) Close when clicking the backdrop
            processPaymentModal.addEventListener('click', (e) => {
                if (e.target === processPaymentModal) closeProcessPaymentModal();
            });

            document.querySelectorAll('.close-process-payment-modal-btn').forEach(btn => btn.addEventListener(
                'click',
                closeProcessPaymentModal));

            const radios = document.querySelectorAll('input[name="payment_method"]');
            const creditCardOptions = document.getElementById('creditCardOptions');
            const cardOption = document.getElementById('cardOption');
            const newCardFields = document.getElementById('newCardFields');
            const cardOnFileDropdown = document.getElementById('cardOnFileDropdown');
            const cardNumberInput = document.getElementById('cardNumber');
            const expiryInput = document.getElementById('expiry');
            const cvcInput = document.getElementById('cvc');
            // ===== Input formatting =====
            cardNumberInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 16).replace(/(.{4})/g, '$1 ')
                    .trim();
            });

            expiryInput.addEventListener('input', function() {
                let val = this.value.replace(/[^0-9]/g, '').substring(0, 4);
                if (val.length >= 3) val = val.substring(0, 2) + '/' + val.substring(2);
                this.value = val;
            });

            cvcInput.addEventListener('input', function() {
                this.value = this.value.replace(/\D/g, '').substring(0, 4);
            });

            radios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.value === 'card') {
                        creditCardOptions.classList.remove('hidden');
                        cardOption.dispatchEvent(new Event('change'));
                    } else {
                        creditCardOptions.classList.add('hidden');
                        newCardFields.classList.add('hidden');
                        cardOnFileDropdown.classList.add('hidden');
                    }
                });
            });

            cardOption.addEventListener('change', function() {
                console.log("Card option changed:", this.value);
                if (!this.value) {
                    newCardFields.classList.remove('hidden');
                    cardOnFileDropdown.classList.add('hidden');
                } else {
                    newCardFields.classList.add('hidden');
                    cardOnFileDropdown.classList.remove('hidden');
                }
            });

            document.getElementById('paymentForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const form = e.target;

                if (!$(form).parsley().isValid()) {
                    $(form).parsley().validate();
                    return;
                }

                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.textContent;


                let endpoint = '#';
                const selectedPaymentMethod = Array.from(radios).find(radio => radio.checked)?.value;
                console.log("Selected payment method:", selectedPaymentMethod, cardOption.value);

                // Helper to send API request
                function processApi(endpoint, form) {
                    const formData = new FormData(form);
                    formData.set('_method', 'PUT');
                    // Never send these:
                    formData.delete('cardNumber');
                    formData.delete('expiry');
                    formData.delete('cvc');
                    if (!submitBtn.disabled) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                    }

                    apiFetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
                                    .getAttribute('content')
                            },
                            body: formData
                        })
                        .then(res => {
                            if (res && res.success) {
                                notyf.success(res.message);
                                window.location.reload();
                            } else {
                                notyf.error(res && res.message);
                            }
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                        });
                }

                if (selectedPaymentMethod === 'cash') {
                    endpoint =
                        '{{ route('admin.order-management.orders.confirm-payment', ':unique_id') }}';
                    endpoint = endpoint.replace(':unique_id', orderUniqueId);

                    showConfirm('Do you want to confirm this payment?', 'Are you sure?').then((result) => {
                        if (result.isConfirmed) {
                            processApi(endpoint, form); // ✅ Only proceed if confirmed
                        }
                    });

                    return; // ✅ Prevent continuing if cash
                } else {
                    endpoint =
                        '{{ route('admin.order-management.orders.charge-credit-card', ':unique_id') }}';
                    endpoint = endpoint.replace(':unique_id', orderUniqueId);
                    const customer_card = document.getElementById('cardOption').value;
                    if (!customer_card) {
                        submitBtn.disabled = true;
                        submitBtn.textContent = 'Saving...';
                        try {

                            // Card fields
                            const firstName = document.getElementById('firstName').value.trim();
                            const lastName = document.getElementById('lastName').value.trim();
                            const cardNumber = document.getElementById('cardNumber').value.replace(/\s/g,
                                '');
                            const expiry = document.getElementById('expiry').value.trim();
                            const cvc = document.getElementById('cvc').value.trim();

                            // Basic validation
                            function luhnCheck(num) {
                                let arr = (num + '').split('').reverse().map(x => parseInt(x));
                                let sum = arr.reduce((acc, val, idx) => {
                                    if (idx % 2) {
                                        val *= 2;
                                        if (val > 9) val -= 9;
                                    }
                                    return acc + val;
                                }, 0);
                                return sum % 10 === 0;
                            }

                            if (!firstName || !lastName) {
                                throw new Error('First name and last name are required.');
                            }

                            if (!/^\d{13,19}$/.test(cardNumber) || !luhnCheck(cardNumber)) {
                                throw new Error('Invalid or missing card number.');
                            }

                            if (!/^\d{2}\/\d{2}$/.test(expiry)) {
                                throw new Error('Invalid or missing expiry date. Use MM/YY.');
                            }

                            const [mm, yy] = expiry.split('/');
                            const now = new Date();
                            const expiryYear = 2000 + parseInt(yy, 10);
                            const expiryMonth = parseInt(mm, 10);

                            if (
                                expiryMonth < 1 || expiryMonth > 12 ||
                                expiryYear < now.getFullYear() ||
                                (expiryYear === now.getFullYear() && expiryMonth < (now.getMonth() + 1))
                            ) {
                                throw new Error('Card expiry is in the past.');
                            }

                            if (!/^\d{3,4}$/.test(cvc)) {
                                throw new Error('Invalid or missing CVC code.');
                            }

                            // Tokenize with Accept.js
                            const [expMonth, expYearShort] = expiry.split('/');
                            const expYear = '20' + expYearShort;

                            const authData = {
                                clientKey: '{{ $paymentSetting['payment_api_public_key'] ?? '' }}',
                                apiLoginID: '{{ $paymentSetting['payment_api_key'] ?? '' }}'
                            };
                            const cardData = {
                                cardNumber: cardNumber,
                                month: expMonth,
                                year: expYear,
                                cardCode: cvc
                            };
                            const secureData = {
                                authData: authData,
                                cardData: cardData
                            };

                            Accept.dispatchData(secureData, function(response) {
                                if (response.messages.resultCode === "Error") {
                                    let errorMsg = response.messages.message.map(m => m.text).join(
                                        ', ');
                                    submitBtn.disabled = false;
                                    submitBtn.textContent = originalText;
                                    return notyf.error('Card Error: ' + errorMsg);
                                } else {
                                    document.getElementById('opaqueDataValue').value = response
                                        .opaqueData.dataValue;
                                    document.getElementById('opaqueDataDescriptor').value = response
                                        .opaqueData.dataDescriptor;

                                    // ✅ Proceed only after tokenization success
                                    processApi(endpoint, form);
                                }
                            });
                        } catch (error) {
                            submitBtn.disabled = false;
                            submitBtn.textContent = originalText;
                            notyf.error(error.message);
                            return;
                        }
                    }else{
                        processApi(endpoint, form);
                    }
                }
            });
        });
    </script>
@endpush
