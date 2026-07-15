@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <section class="bg-white py-10 px-4 md:px-10 pt-130 pb-60">
        <div class="container mx-auto 2xl:max-w-[1320px] md:max-w-[720px] lg:max-w-[1140px] px-[30px] md:px-[.7rem]">
            <div class="flex flex-col md:flex-row gap-2">
                <!-- LEFT SECTION: Confirmation + Customer Info -->
                <div class="w-full md:w-1/2 md:border-r px-0 lg:px-4 md:pr-8 pb-12 space-y-6">
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
                            <span class="font-normal w-40 flex-shrink-0">Order Number:</span>
                            <span class="text-custom-blue">{{ $order->order_number ?? '' }}</span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Full name:</span>
                            <span class="text-custom-blue">{{ $order->shippingAddress->full_name ?? '' }}</span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Phone:</span>
                            <span class="text-custom-blue">{{ $order->shippingAddress->phone ?? '' }}</span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Email:</span>
                            <span class="text-custom-blue break-all w-full sm:w-auto">{{ $order->shippingAddress->email ?? '' }}</span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Address:</span>
                            <span class="text-custom-blue text-justify">
                                {{ $order->shippingAddress->full_address ?? '' }}
                            </span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Payment method:</span>
                            <span class="text-custom-blue">{{ $order->last_payment_type?->label() ?? '' }}</span>
                        </div>
                        <div class="flex">
                            <span class="font-normal w-40 flex-shrink-0">Payment status:</span>
                            {!! \App\Helpers\CustomHelper::paymentStatusBadge($order->last_payment_status) !!}
                        </div>
                    </div>

                    <!-- Button -->
                    <div>
                        <a href="/"
                            class="inline-block bg-yellow-400 text-black font-medium px-6 py-3 rounded-lg hover:bg-yellow-500 transition">
                            CONTINUE SHOPPING
                        </a>

                          {{-- @if (session()->has('impersonated_by_admin')) --}}

                         <a href="{{ route('front.checkout.receipt-download', $encryptedid) }}"
                            class="inline-flex items-center gap-2 bg-yellow-400 text-black font-medium px-6 py-3 rounded-lg hover:bg-yellow-500 transition">

                                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                        d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
                                </svg>

                                <span>Print Receipt</span>
                            </a>
                            {{-- @endif --}}

                    </div>

                    
                </div>

                <!-- Order Summary -->
                <div id="orderSummary" class="w-full md:w-1/2 mt-0 lg:mt-10 pl-0 lg:pl-6">
                    {!! $thankYouComponent !!}
                </div>
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
