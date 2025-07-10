@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <section class="bg-white py-10 px-4 md:px-10 pt-130 pb-60">
        <div class="max-w-7xl mx-auto flex flex-col lg:flex-row gap-6">
            <!-- LEFT SECTION: Confirmation + Customer Info -->
            <div class="lg:w-2/3 space-y-6">
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
                    <p><span class="font-normal">Full name:</span> <span class="text-custom-blue ml-5">{{ $order->customer_name ?? '' }}</span></p>
                    <p><span class="font-normal">Phone:</span> <span class="text-custom-blue ml-5">{{ $order->customer_phone ?? '' }}</span></p>
                    <p><span class="font-normal">Email:</span> <span class="text-custom-blue ml-5">{{ $order->customer_email ?? '' }}</span></p>
                    <p><span class="font-normal">Address:</span>
                        <span class="text-custom-blue ml-5">
                            {{ $order->shippingAddress->address ?? '' }},
                            {{ $order->shippingAddress->city ?? '' }},
                            {{ $order->shippingAddress->state ?? '' }},
                            {{ $order->shippingAddress->zip_code ?? '' }}
                        </span>
                    </p>
                    <p><span class="font-normal">Payment method:</span> <span class="text-custom-blue ml-5">{{ $order->payment_type ?? '' }}</span></p>
                    <p><span class="font-normal">Payment status:</span>
                        <span class="@if(($order->status ?? '') === 'PENDING') text-yellow-500 font-semibold @elseif(($order->status ?? '') === 'PAID') text-green-600 font-semibold @else text-gray-500 @endif ml-5">
                            {{ strtoupper($order->status ?? '') }}
                        </span>
                    </p>
                </div>
                <!-- Button -->
                <div>
                    <a href="/"
                        class="inline-block bg-yellow-400 text-black font-medium px-6 py-3 rounded-lg hover:bg-yellow-500 transition">
                        CONTINUE SHOPPING
                    </a>
                </div>
            </div>

            <!-- Cart Summary -->
            <div id="cartSummary" class="w-1/3 mt-10 pl-6">
                <div class="border-b pb-4 mb-6">
                    <h2 class="text-2xl font-bold mb-2">Cart Summary</h2>
                    <p class="text-sm text-gray-600">Review your items before proceeding to checkout.</p>
                </div>
                <div class="border-b pb-6">
                    <ul class="flex flex-col">
                        <li class="flex justify-between mb-1">
                            <span class="">Subtotal:</span>
                            <span class=" font-bold">+ $0.00</span>
                        </li>
                        <li class="flex justify-between mb-1">
                            <span class="">Tax</span>
                            <span class=" font-bold">+ $0.00</span>
                        </li>
                        <li class="flex justify-between mb-1">
                            <span class=" font-bold">Total</span>
                            <span class=" font-bold">+ $0.00</span>
                        </li>
                    </ul>
                </div>
            </div>
    </section>
@endsection

@push('js')
    <script>
        window.CartStorage.clearCart();
    </script>
@endpush
