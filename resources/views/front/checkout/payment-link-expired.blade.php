@extends('front.layouts.app')

@section('title', 'Payment Link Expired')

@section('content')
    <section class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-[#f9fafc]">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div class="pt-[100px] pb-[20px]">
                <h1 class="text-[28px] md:text-[34px] lg:text-[40px] tracking-[-2px] leading-[110%] font-bold">
                    Payment Link
                </h1>
            </div>
        </div>
    </section>

    <section class="py-16">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">
            <div class="max-w-lg mx-auto text-center">

                {{-- Icon --}}
                <div class="flex justify-center mb-6">
                    <div class="w-20 h-20 rounded-full bg-yellow-100 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>

                <h2 class="text-2xl font-bold mb-3">This payment link is no longer active</h2>
                <p class="text-gray-500 mb-8 leading-relaxed">
                    The link you followed has either expired or has already reached its usage limit.
                    If you still need to make a payment, please contact us and we will send you a new link.
                </p>

                <div class="flex flex-col sm:flex-row gap-3 justify-center">
                    <a href="{{ route('front.home.index') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-lg bg-yellow-400 hover:bg-yellow-500 text-black font-semibold transition-colors">
                        Go to Homepage
                    </a>
                    <a href="tel:{{ config('app.business_phone', '') }}"
                       class="inline-flex items-center justify-center px-6 py-3 rounded-lg border border-gray-300 hover:bg-gray-50 text-gray-700 font-semibold transition-colors">
                        Contact Us
                    </a>
                </div>

            </div>
        </div>
    </section>
@endsection
