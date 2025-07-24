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
                    <span
                        class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                        <span class="w-2 h-2 bg-white rounded-full mr-2"></span>
                        PENDING PAYMENT
                    </span>
                    @if ($order->last_payment_type !== 'Card')
                        <button
                            class="px-4 py-1 text-xs font-semibold bg-green-600 text-white rounded-full hover:bg-green-700">
                            Add to Account
                        </button>
                        <button id="confirmPaymentBtn"
                            class="px-4 py-1 text-xs font-semibold bg-blue-600 text-white rounded-full hover:bg-blue-700">
                            Confirm payment
                        </button>
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
            <a href="javascript:void(0);" class="inline-flex items-center hover:underline">
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
                    <h3 class="text-sm font-semibold text-gray-800">Shipping Information</h3>
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
                    id="shipping_same_as_billing">Same as billing</p>

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
                            <a href="javascript:void(0);" class="text-blue-600 font-semibold hover:underline">
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
                                @if ($orderProduct->product_data['product_type'] === 'Rental')
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
                                                    <button id="delivery_transport_mode-btn-{{ $orderProduct->unique_id }}"
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
                                                <div id="pickup_transport_mode_{{ $orderProduct->unique_id }}" x-data="{ selected: '{{ $orderProduct->pickup_transport_mode ?? 'Store' }}', open: false }"
                                                    class="relative w-full">
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
                            let url =
                                '{{ route('admin.order-management.orders.confirm-payment', ':unique_id') }}';
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
                                        notyf.success('Payment confirmed!');
                                        setTimeout(() => window.location.reload(), 800);
                                    } else {
                                        notyf.error(res && res.message ? res.message :
                                            'Failed to confirm payment.');
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
                    let lastValue = deliveryTime.value
                        ? flatpickr.parseDate(deliveryTime.value, "h:i K")
                        : null;

                    flatpickr(deliveryTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K",
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse dateStr to a Date object (or null)
                            const newValue = dateStr
                                ? flatpickr.parseDate(dateStr, "h:i K")
                                : null;

                            // Only call if both are valid dates and times are different
                            // Or if lastValue was null but now we have a value
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue.getTime()) ||
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
                        updateScheduleField('delivery', 'delivery_transport_mode', deliveryType.value);
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
                    let lastValue = returnTime.value
                        ? flatpickr.parseDate(returnTime.value, "h:i K")
                        : null;

                    flatpickr(returnTime, {
                        enableTime: true,
                        noCalendar: true,
                        dateFormat: "h:i K", // 12-hour format
                        time_24hr: false,
                        onClose: function(selectedDates, dateStr) {
                            // Parse the new value as a Date object
                            const newValue = dateStr
                                ? flatpickr.parseDate(dateStr, "h:i K")
                                : null;

                            // Only trigger if the date/time actually changed
                            const changed = (
                                (lastValue && newValue && newValue.getTime() !== lastValue.getTime()) ||
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
                document.getElementById('addressModalTitle').textContent = `Edit ${type} Address`;

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


        });
    </script>
@endpush
