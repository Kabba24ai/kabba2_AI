@extends('front.layouts.app')

@section('title', 'View Order')


@push('css')
@endpush

@section('content')
<div class="pt-24 px-4 lg:px-6 pb-10">
    <div class="max-w-7xl mx-auto space-y-6">
        {{-- Order Header Section --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">

                <!-- LEFT -->
                <div class="space-y-3">

                <div class="text-xl font-semibold text-gray-800">
                    Order {{ $order->order_number }}
                </div>

                <div class="flex flex-wrap gap-4 text-sm text-gray-600">

                    <div>
                        <span class="font-medium text-gray-700">PO ID:</span>
                        {{ $order->po_id ?? '—' }}
                    </div>

                    @if ($order->reference_order_number)
                    <div>
                        <span class="font-medium text-gray-700">Reference:</span>
                        {{ $order->reference_order_number }}
                    </div>
                    @endif

                    <div>
                        <span class="font-medium text-gray-700">Customer:</span>
                        {{ $order->customer_name }}
                    </div>

                    <div>
                        <span class="font-medium text-gray-700">Company:</span>
                        {{ $order->company_name ?? '—' }}
                    </div>

                </div>

            </div>

                <!-- STATUS -->
                <div class="flex flex-wrap gap-2 items-center">

                    @if ($order->last_payment_status === 'Pending' || $order->last_payment_status === 'Failed')
                    <span class="px-3 py-1 text-xs font-semibold bg-yellow-500 text-white rounded-full">
                        Pending Payment
                    </span>
                    @endif

                    @if ($order->is_paid)
                    <span class="px-3 py-1 text-xs font-semibold bg-green-500 text-white rounded-full">
                        Paid via {{ $order->last_payment_type->label() }}
                    </span>
                    @endif

                    @if ($order->last_payment_status === 'Failed')
                    <span class="px-3 py-1 text-xs font-semibold bg-red-500 text-white rounded-full">
                        Payment Failed
                    </span>
                    @endif

                </div>

                <!-- ACTION BUTTONS -->
                <div class="flex gap-2 flex-wrap">

                     <a href="{{ route('front.customer.dashboard.index') }}" class="inline-flex items-center px-6 py-2 rounded-md text-white bg-blue-600 text-sm font-medium shadow transition">
                        <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg"
         fill="none" viewBox="0 0 24 24" stroke-width="1.5"
         stroke="currentColor">

        <path stroke-linecap="round" stroke-linejoin="round"
              d="M15.75 19.5L8.25 12l7.5-7.5" />

    </svg>

                        Back
                    </a>

                 

                    <a href="{{ route('front.customer.dashboard.order.receipt-download', $order->unique_id) }}"
                        class="flex items-center gap-1 px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        <x-heroicon-o-printer class="w-4 h-4"/>
                        Print Receipt
                    </a>

                   

                </div>

            </div>
        </div>


        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">

            {{-- Billing Info --}}
             <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
                <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Billing Information</h3>
                        </div>
                </div>
                <div class="p-6">
                    <div class="text-sm text-gray-700 space-y-2 billing-address-section">
                        <input type="hidden" id="billing_first_name_input"
                            data-first-name="{{ $order->billingAddress->first_name ?? '' }}">
                        <input type="hidden" id="billing_last_name_input"
                            data-last-name="{{ $order->billingAddress->last_name ?? '' }}">
                        <input type="hidden" id="billing_email_input"
                            data-email="{{ $order->billingAddress->email ?? '' }}">
                        <input type="hidden" id="billing_phone_input"
                            data-phone="{{ $order->billingAddress->phone ?? '' }}">
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
            <div class="bg-white rounded-xl border border-gray-200">
                <div class="px-4 py-4 rounded-t-lg border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-semibold text-gray-800">Delivery Information</h3>
                        
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
                    <p class="text-sm text-gray-600 shipping-address-section {{ $order->shippingAddress->isSameAs($order->billingAddress) ? '' : 'hidden' }}"
                        id="shipping_same_as_billing">Same as Billing</p>

                </div>
            </div>

        </div>


        <div class="bg-white rounded-xl shadow-sm p-6 mb-6 space-y-6">
            <h3 class="text-base font-semibold text-gray-800"> Orders Details </h3>

            {{-- Equipment Info --}}
            <div class="space-y-6">
                @foreach ($order->products as $index => $orderProduct)
                    <div class="border-gray-200  rounded-xl p-5 bg-white shadow-sm">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-start">

        {{-- LEFT SIDE : Image + Name --}}
        <div class="flex gap-4">

            <div class="w-[130px] h-[130px] bg-gray-100 rounded-lg flex items-center justify-center text-gray-400">
                @if ($orderProduct?->product?->image_url)
                    <img src="{{ $orderProduct->product->image_url }}"
                         alt="{{ $orderProduct->product_name }}"
                         class="object-contain w-full h-full rounded" />
                @else
                    <span class="text-xs">No Image</span>
                @endif
            </div>

            <div class="space-y-1">
                <a href="javascript:void(0);"
                   class="text-blue-600 font-semibold hover:underline text-base">
                    {{ $orderProduct->product_name }}
                </a>

                <div class="text-sm text-gray-500">
                    Variant: {{ ucwords($orderProduct->product_data['product_variant'] ?? '—') }}
                </div>

                <div class="text-sm text-gray-500">
                    Quantity: {{ $orderProduct->quantity }}
                </div>
            </div>

        </div>


        {{-- RIGHT SIDE : Prices --}}
        <div class="border-gray-200  rounded-lg p-4 text-sm text-gray-700 space-y-2 bg-gray-50">

            <div class="flex justify-between">
                <span>Product Cost (x{{ $orderProduct->quantity ?? 1 }})</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->price) }}</span>
            </div>


            @if (!empty($orderProduct->distance_range))
            <div class="pt-2 border-t">
                <div class="text-xs text-gray-500 mb-1">Distance Range</div>

                <div class="flex justify-between">
                    <span>
                        {{ ucfirst($orderProduct->distance_type) }}
                        ({{ ucfirst($orderProduct->distance_range) }})
                    </span>

                    <span>
                        {{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->product_data['service_option_price'] ?? 0) }}
                    </span>
                </div>
            </div>
            @endif


            @if ($orderProduct->product_data && count($orderProduct->product_data))

            <div class="pt-2 border-t">
                <div class="text-xs text-gray-500 mb-1">Options</div>

                @foreach ($orderProduct->product_data['product_rental_items_prices'] as $rentalKey => $rentalPrice)

                @php
                $case = collect(\App\Enums\Products\ProductCustomStaticLabel::cases())
                    ->firstWhere('name', $rentalKey)?->value;
                $quantity = $case ? $orderProduct->quantity : 1;
                @endphp

                <div class="flex justify-between">
                    <span>
                        {{ $case ?? ucwords(str_replace('_',' ',preg_replace('/^rental_/','',$rentalKey))) }}
                        <span class="text-xs text-gray-400">(x{{ $quantity }})</span>
                    </span>

                    <span>
                        {{ \App\Helpers\CustomHelper::formatCurrency($rentalPrice) }}
                    </span>
                </div>

                @endforeach

            </div>

            @endif


            <div class="flex justify-between font-semibold border-t pt-2">
                <span>Subtotal</span>
                <span>{{ \App\Helpers\CustomHelper::formatCurrency($orderProduct->sub_total) }}</span>
            </div>

        </div>

    </div>
</div>
                    <hr class="col-span-full my-2 border-t border-gray-300" />
                @endforeach
            </div>

            {{-- Summary & Notes --}}
            <div class="bg-white border-gray-300 rounded-xl shadow-sm p-6 max-w-md ml-auto space-y-3">

                <div class="flex justify-between text-sm">
                    <span>Subtotal</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->subtotal) }}</span>
                </div>

                <div class="flex justify-between text-sm">
                    <span>Taxes</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->tax_amount) }}</span>
                </div>

                <div class="border-t pt-3 flex justify-between text-lg font-semibold">
                    <span>Total</span>
                    <span>{{ \App\Helpers\CustomHelper::formatCurrency($order->grand_total) }}</span>
                </div>

            </div>
           

        </div>
    </div>
</div>
@endsection


@push('js')


@endpush