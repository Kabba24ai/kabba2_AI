@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <section class="bg-white py-10 px-4 md:px-10 pt-130 pb-60">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-6">
            <!-- LEFT SECTION: Confirmation + Customer Info -->
            <div class="w-full md:w-1/2 md:border-r px-4 md:pr-8 pb-12 space-y-6">
                <!-- Confirmation Box -->
                <div class="flex items-center gap-4 pt-6">
                    <div class="text-green-500 text-4xl">
                        <img src="{{ asset('storage/front/images/thankyou-right.png') }}" alt=""
                            class="h-full md:w-20 w-20">
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-black">Your order has been successfully placed</h2>
                        <p class="text-gray-600">Thank you for your business!</p>
                    </div>
                </div>
                <!-- Customer Info -->
                <div class="space-y-2">
                    <h3 class="text-lg font-semibold">Customer information</h3>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Full name:</span>
                        <span class="text-custom-blue">{{ $order->customer_name ?? '' }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Phone:</span>
                        <span class="text-custom-blue">{{ $order->customer_phone ?? '' }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Email:</span>
                        <span class="text-custom-blue">{{ $order->customer_email ?? '' }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Address:</span>
                        <span class="text-custom-blue text-justify">
                            {{ $order->shippingAddress->address ?? '' }}<br>
                            {{ $order->shippingAddress->city ?? '' }},
                            {{ $order->shippingAddress->state ?? '' }}<br>
                            {{ $order->shippingAddress->zip_code ?? '' }}
                        </span>
                    </div>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Payment method:</span>
                        <span class="text-custom-blue">{{ $order->last_payment_type ?? '' }}</span>
                    </div>
                    <div class="flex">
                        <span class="font-normal w-40 flex-shrink-0">Payment status:</span>
                        {!! $order->last_payment_badge !!}
                    </div>
                </div>

                <!-- Button -->
                <div>
                    <a href="/"
                        class="inline-block bg-yellow-400 text-black font-medium px-6 py-3 rounded-lg hover:bg-yellow-500 transition">
                        CONTINUE SHOPPING
                    </a>
                </div>
            </div>

            <!-- Order Summary -->
            <div id="orderSummary" class="w-full md:w-1/2 mt-10 pl-6">
                {!! $thankYouComponent !!}
            </div>
    </section>
@endsection

@push('js')
    <script>
        // Clear the cart storage after order completion
        document.addEventListener('DOMContentLoaded', function() {
            window.CartStorage.clearCart();
        });
    </script>
@endpush
