@extends('front.layouts.app')

@section('title', $title)

@section('content')

{{-- ── Hero ──────────────────────────────────────────────────────────────── --}}
<section class="relative pt-15 h-[220px] sm:h-[280px] md:h-[320px] lg:h-[360px] overflow-hidden">

    <img
        src="{{ asset('storage/front/images/banner.jpg') }}"
        alt="{{ $store->store_name }}"
        class="absolute inset-0 w-full h-full object-cover
               object-[center_top] sm:object-[center_20%] md:object-[center_30%] lg:object-[center_-17%]"
        aria-hidden="true"
    >

    <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/35 to-black/10"></div>

    <div class="relative z-10 h-full container mx-auto px-5 sm:px-6 lg:px-8 flex flex-col justify-center">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-1.5 text-xs text-white/60 mb-3" aria-label="Breadcrumb">
            <a href="{{ route('front.home_v2.index') }}" class="hover:text-white transition-colors">Home</a>
            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-white/80">{{ $store->store_name }}</span>
        </nav>

        <div class="max-w-full sm:max-w-[520px] lg:max-w-[620px]">

            <h1 class="text-[26px] leading-tight sm:text-[32px] md:text-[38px] lg:text-[34px] font-semibold uppercase text-white">
                {{ $store->store_name }}
            </h1>

            @if($store->address)
                <p class="mt-2 text-sm sm:text-base leading-relaxed text-white/80">
                    {{ $store->address }},
                    {{ $store->city }}{{ $store->state?->name ? ', ' . $store->state->name : '' }} {{ $store->zip_code }}
                </p>
            @endif

        </div>
    </div>

</section>

{{-- ── Store Details + Hours ───────────────────────────────────────────── --}}
<section class="bg-white py-10 md:py-14">
    <div class="max-w-screen-xl mx-auto px-4 md:px-6 lg:px-8">

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-16">

            {{-- ── Left: Store Info ──────────────────────────────────────── --}}
            <div>

                <h2 class="text-lg font-semibold uppercase tracking-wide text-[#171636] mb-6 pb-3 border-b border-gray-200">
                    Store Information
                </h2>

                <ul class="space-y-4">

                    {{-- Phone --}}
                    @if($store->phone)
                        <li class="flex items-start gap-4">
                            <span class="shrink-0 w-10 h-10 rounded-full bg-[#1F1D4E] flex items-center justify-center mt-0.5">
                                <x-heroicon-s-phone class="w-4 h-4 text-white" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Phone</p>
                                <a href="tel:{{ preg_replace('/[^+\d]/', '', $store->phone) }}"
                                   class="mt-0.5 block text-base font-bold text-gray-900 hover:text-yellow-500 transition-colors">
                                    {{ $store->phone }}
                                </a>
                            </div>
                        </li>
                    @endif

                    {{-- Email --}}
                    @if($store->email)
                        <li class="flex items-start gap-4">
                            <span class="shrink-0 w-10 h-10 rounded-full bg-[#1F1D4E] flex items-center justify-center mt-0.5">
                                <x-heroicon-s-envelope class="w-4 h-4 text-white" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Email</p>
                                <a href="mailto:{{ $store->email }}"
                                   class="mt-0.5 block text-base font-bold text-gray-900 hover:text-yellow-500 transition-colors">
                                    {{ $store->email }}
                                </a>
                            </div>
                        </li>
                    @endif

                    {{-- Address --}}
                    @if($store->address)
                        <li class="flex items-start gap-4">
                            <span class="shrink-0 w-10 h-10 rounded-full bg-[#1F1D4E] flex items-center justify-center mt-0.5">
                                <x-heroicon-s-map-pin class="w-4 h-4 text-white" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">Address</p>
                                <p class="mt-0.5 text-base font-bold text-gray-900 leading-snug">
                                    {{ $store->address }}<br>
                                    {{ $store->city }}{{ $store->state?->name ? ', ' . $store->state->name : '' }} {{ $store->zip_code }}
                                </p>
                            </div>
                        </li>
                    @endif

                    {{-- Details --}}
                    @if($store->details)
                        <li class="flex items-start gap-4">
                            <span class="shrink-0 w-10 h-10 rounded-full bg-[#1F1D4E] flex items-center justify-center mt-0.5">
                                <x-heroicon-s-information-circle class="w-4 h-4 text-white" />
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-gray-500 uppercase tracking-widest">About</p>
                                <p class="mt-0.5 text-sm text-gray-700 leading-relaxed">{{ $store->details }}</p>
                            </div>
                        </li>
                    @endif

                </ul>

                {{-- ── Hours of Operation ─────────────────────────────── --}}
                @if($store->hoursOfOperation->isNotEmpty())
                    <h2 class="text-lg font-semibold uppercase tracking-wide text-[#171636] mt-10 mb-6 pb-3 border-b border-gray-200">
                        Hours of Operation
                    </h2>

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="w-full text-sm">
                            <tbody class="divide-y divide-gray-100">
                                @foreach($days as $day)
                                    @php $h = $hoursMap->get($day); @endphp
                                    <tr class="flex items-center px-4 py-3 {{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                                        <td class="w-32 font-semibold text-gray-800">{{ $day }}</td>
                                        @if(!$h || $h->is_closed)
                                            <td class="flex-1 text-right">
                                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-red-100 text-red-600 text-xs font-semibold">Closed</span>
                                            </td>
                                        @else
                                            <td class="flex-1 text-right text-gray-700">
                                                {{ \Carbon\Carbon::parse($h->start_time)->format('g:i A') }}
                                                –
                                                {{ \Carbon\Carbon::parse($h->end_time)->format('g:i A') }}
                                                {{-- @if($h->is_lunch_required)
                                                    <span class="ml-2 text-xs text-gray-400">(Lunch break)</span>
                                                @endif --}}
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

            </div>

            {{-- ── Right: Google Map ─────────────────────────────────────── --}}
            <div class="flex flex-col">

                <h2 class="text-lg font-semibold uppercase tracking-wide text-[#171636] mb-6 pb-3 border-b border-gray-200">
                    Find Us
                </h2>

                @php
                    $mapQuery = ($store->latitude && $store->longitude)
                        ? $store->latitude . ',' . $store->longitude
                        : urlencode($store->full_address);
                    $hasMap = ($store->latitude && $store->longitude) || $store->address;
                @endphp

                @if($hasMap)
                    <div class="flex-1 min-h-[340px] rounded-xl overflow-hidden border border-gray-200 shadow-sm">
                        <iframe
                            width="100%"
                            height="100%"
                            style="min-height: 340px; border: 0;"
                            loading="lazy"
                            allowfullscreen
                            referrerpolicy="no-referrer-when-downgrade"
                            src="https://maps.google.com/maps?q={{ $mapQuery }}&z=15&output=embed">
                        </iframe>
                    </div>

                    <a href="https://maps.google.com/?q={{ $mapQuery }}"
                       target="_blank"
                       rel="noopener noreferrer"
                       class="mt-3 inline-flex items-center gap-1.5 text-yellow-500 uppercase font-semibold text-xs hover:text-yellow-600 transition-colors">
                        Get Directions
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                @else
                    <div class="flex-1 min-h-[200px] rounded-xl border border-gray-200 bg-gray-50 flex items-center justify-center">
                        <p class="text-sm text-gray-400">Map location not available</p>
                    </div>
                @endif

            </div>

        </div>

    </div>
</section>

@endsection
