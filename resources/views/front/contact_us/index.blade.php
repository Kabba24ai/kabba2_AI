@extends('front.layouts.app')

@section('title', $title)

@section('content')
    <!-- Page Title Section -->
    <section
        class="transform transition-all duration-300 ease-in-out md:border-l-[30px] md:border-l-[#fff] md:border-r-[30px] md:border-r-[#fff] bg-light border-b-0">
        <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-[30px] md:px-0">

            {{-- Top heading --}}
            <div class="pt-[100px] pb-10 text-center">
                <h1 class="text-3xl md:text-4xl lg:text-5xl font-bold tracking-tight">Contact Us</h1>
                <p class="mt-4 text-base md:text-lg text-slate-700">
                    Speak with a human – No frustrating menus and bots
                </p>
                <p class="mt-1 text-xs md:text-sm text-slate-500 italic">
                    (We might be on the phone with others when you call, but we’ll always call you back as quickly as
                    possible)
                </p>
            </div>

            {{-- Location cards --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 pb-[60px]">

                @foreach ($stores as $store)
                    <div class="bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden flex flex-col">
                        <div class="px-8 pt-8 pb-6">
                            <h2 class="text-xl md:text-2xl font-semibold mb-6">
                                Rent 'n King - {{ $store->store_name }}
                            </h2>

                            {{-- Address --}}
                            <div class="flex items-start gap-3 mb-6 text-sm md:text-base">
                                <span class="mt-1 text-blue-500" >
                                    <i class="fa-solid fa-location-dot"></i>
                                </span>
                                <div class="text-left">
                                    <p class="font-medium">{{ $store->address }}</p>
                                    <p class="text-slate-600">{{ $store->city }}, {{ $store?->state?->name }}
                                        {{ $store->zip_code }}</p>
                                </div>
                            </div>

                            {{-- Buttons --}}
                            <div class="flex flex-wrap gap-4 mb-6 justify-between" >
                                <a href="tel:16158156734"
                                    class="inline-flex items-center justify-center gap-3 rounded-lg py-3 text-sm font-semibold transition">                                    
                                    <span class="text-blue-500"><i class="fa-solid fa-phone"></i></span>
                                    <span class="text-gray-400 text-sm"> Call Us: </span>
                                    <span>{{ $store->phone }}</span>
                                </a>

                                <a href="sms:{{ $store->phone }}?body=Hello, I’m interested in learning more about your rental services. Could you please provide information regarding current availability and pricing?"
                                    class="inline-flex items-center justify-center gap-3 rounded-lg border border-slate-300 bg-white px-6 py-3 text-sm font-medium text-slate-700 hover:bg-slate-50 transition">
                                    <span><i class="fa-regular fa-comment-dots"></i></span>
                                    <span>Click to Text</span>
                                </a>
                            </div>

                            {{-- Info bar --}}
                            <div class="mt-2 border-l-4 border-blue-600 bg-gray-50 px-6 py-4 text-sm text-slate-700">
                                {{ $store->details }}
                            </div>
                        </div>

                        {{-- Map / image area --}}
                        <div class="mt-6 bg-[#e3e3e3] w-full h-[360px]">
                            <iframe class="w-full h-full"
                                src="https://maps.google.com/?q={{ $store->latitude }},{{ $store->longitude }}&output=embed"
                                style="border:0;" allowfullscreen="" loading="lazy"
                                referrerpolicy="no-referrer-when-downgrade"></iframe>
                        </div>
                    </div>
                @endforeach


            </div>

        </div>
    </section>
@endsection
