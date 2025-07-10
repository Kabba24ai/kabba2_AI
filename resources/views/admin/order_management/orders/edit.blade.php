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
                <!-- Pending Payment -->
                <span class="inline-flex items-center px-4 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
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
                    <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" />
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
            <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                    <x-heroicon-o-pencil-square class="w-4 h-4 text-gray-400 hover:text-gray-600 cursor-pointer" />
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
                                <img src="{{ $orderProduct->product->image_url }}" alt="{{ $orderProduct->product_name }}"
                                    class="object-contain w-full h-full rounded" />
                            @else
                                Image not available
                            @endif
                        </div>
                        <div>
                            <a href="#" class="text-blue-600 font-semibold hover:underline">
                                {{ $orderProduct->product_name }} - {{ ucwords($orderProduct->product_data['product_variant'] ?? '') }}
                            </a>
                            <p class="text-sm text-gray-500">Equipment ID: {{ $orderProduct->sku ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="border rounded p-4 text-sm text-gray-700 space-y-2 bg-gray-50">
                        <div class="flex justify-between">
                            <span>Product Cost(x{{ $orderProduct->quantity ?? 1 }})</span>
                            <span>{{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->price) }}</span>
                        </div>

                        @if ($orderProduct->product_data && count($orderProduct->product_data))
                            <div class="flex justify-between">
                                <span>Options</span>
                            </div>
                            <ul class="pl-5 list-disc text-gray-600 text-sm">
                                @foreach ($orderProduct->product_data['product_rental_items_prices'] as $rentalKey => $rentalPrice)
                                    <li class="flex justify-between">
                                        <span>
                                            {{ ucwords(str_replace('_', ' ', preg_replace('/^rental_/', '', $rentalKey))) }}
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
                                            @if (!empty($option['charged']) && $option['charged'] === 'Unlimited')
                                                <span
                                                    class="text-xs text-gray-400">(x{{ $orderProduct->quantity ?? 1 }})</span>
                                            @endif
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

                    @if ($orderProduct->note)
                        <div class="text-sm px-3 py-2 bg-gray-100 rounded text-gray-600 mb-4">
                            {{ $orderProduct->note }}
                        </div>
                    @endif
                @endforeach
            </div>

            {{-- Schedule Panels --}}
            <div class="bg-white rounded-md shadow-sm p-6 space-y-6">

                {{-- Delivery Schedule --}}
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <span>📦</span> Delivery Schedule
                    </div>
                    <div class="grid grid-cols-5 gap-3 text-sm">
                        <input type="date" value="2025-05-16" class="border rounded px-3 py-2" />
                        <input type="time" value="14:00" class="border rounded px-3 py-2" />
                        <select class="border rounded px-3 py-2">
                            <option selected>Delivery</option>
                        </select>
                        <select class="border rounded px-3 py-2 text-blue-600">
                            <option selected>Completed</option>
                        </select>
                        <select class="border rounded px-3 py-2">
                            <option selected>Bon Aqua</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <select class="border rounded px-3 py-2 text-sm w-1/3">
                            <option selected>John Smith</option>
                        </select>
                    </div>
                </div>

                {{-- Return Schedule --}}
                <div class="space-y-3">
                    <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                        <span>📦</span> Return Schedule
                    </div>
                    <div class="grid grid-cols-5 gap-3 text-sm">
                        <input type="date" value="2025-05-19" class="border rounded px-3 py-2" />
                        <input type="time" value="09:00" class="border rounded px-3 py-2" />
                        <select class="border rounded px-3 py-2">
                            <option selected>Return</option>
                        </select>
                        <select class="border rounded px-3 py-2 text-yellow-600">
                            <option selected>Pending</option>
                        </select>
                        <select class="border rounded px-3 py-2">
                            <option selected>Bon Aqua</option>
                        </select>
                    </div>
                    <div class="mt-2">
                        <select class="border rounded px-3 py-2 text-sm w-1/3">
                            <option selected disabled>Select Technician</option>
                        </select>
                    </div>
                </div>

                {{-- Status Checklist --}}
                <div class="border-t pt-4 grid grid-cols-6 gap-2 text-center text-xs font-medium text-gray-700">
                    <div>
                        <div class="text-red-600 text-lg font-bold">✗</div>
                        <span>Terms</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-lg font-bold">✓</div>
                        <span>License</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Checklist</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Machine Hours</span>
                    </div>
                    <div>
                        <div class="text-green-600 text-sm font-bold">D <span class="text-red-500">R</span></div>
                        <span>Video</span>
                    </div>
                    <div class="col-span-6 mt-2 text-[10px] text-gray-400">
                        ✓ Completed | ● Pending | ✗ N/A | <span class="text-blue-600">D = Delivery</span> | <span
                            class="text-red-500">R = Return</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Summary & Notes --}}
        <div class="grid md:grid-cols-2 gap-4">
            <div class="text-sm text-gray-700 space-y-2 border-t pt-4">
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
            <div class="space-y-2">
                <label class="block text-sm font-semibold text-gray-700">Order Notes:</label>
                <input type="text" value="{{ $order->order_note }}"
                    class="w-full border rounded px-3 py-2 text-sm text-gray-800" />
                <button class="mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">
                    Save Note
                </button>
            </div>
        </div>
    </div>


@endsection

@push('js')
@endpush
