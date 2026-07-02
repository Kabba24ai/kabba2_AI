{{-- ============================================================
     Section: Contact Strip
     White card floated over the hero's bottom edge.
     Desktop: 4 cols · Tablet: 2×2 · Mobile: stacked
     Dynamic data:
       Column 1 — $branding['site_phone']
       Columns 2 & 3 — first two active stores ($stores)
============================================================ --}}

@php
    $store1 = $stores->get(0);
    $store2 = $stores->get(1);
@endphp

<section id="home-v2-contact-strip" class="relative z-20 pb-2 md:pb-0 mb-5">
    <div class="container mx-auto px-4 md:px-6 lg:px-8
                -mt-12 md:-mt-16 lg:-mt-20">
        <div class="text-center uppercase text-[#171636] mt-6 mb-6">
            <p class="text-sm md:text-sm font-semibold tracking-wider">
                Need Help Finding The Right Equipment?
            </p>
            <h2 class="mt-1 text-lg md:text-2xl font-semibold tracking-wide leading-none">
                Call Our Rental Specialists
            </h2>
        </div>
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 bg-gray-100 gap-px">

                {{-- ── Column 1: Main Sales Line ──────────────────────── --}}
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-phone class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            Main Sales Line
                        </p>
                        @if (!empty($branding['site_phone']))
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $branding['site_phone']) }}"
                               class="mt-2 block text-lg font-bold text-yellow-500 leading-tight hover:text-yellow-600 transition-colors">
                                {{ $branding['site_phone'] }}
                            </a>
                        @endif
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            Questions? We're here to help! 
                        </p>
                    </div>

                </div>

                {{-- ── Column 2: Store 1 ───────────────────────────────── --}}
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $store1?->store_name ?? 'Store Location' }}
                        </p>
                        @if ($store1?->phone)
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $store1->phone) }}"
                               class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                                {{ $store1->phone }}
                            </a>
                        @endif
                        @if ($store1)
                            <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                                {{ $store1->address }}<br>
                                {{ $store1->city }}{{ $store1->state?->name ? ', ' . $store1->state->name : '' }} {{ $store1->zip_code }}
                            </p>
                            <a href="#" class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                    uppercase font-semibold text-xs transition-colors">
                                View Store
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif
                    </div>

                </div>

                {{-- ── Column 3: Store 2 ───────────────────────────────── --}}
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-s-map-pin class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            {{ $store2?->store_name ?? 'Second Store' }}
                        </p>
                        @if ($store2?->phone)
                            <a href="tel:{{ preg_replace('/[^+\d]/', '', $store2->phone) }}"
                               class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                                {{ $store2->phone }}
                            </a>
                        @endif
                        @if ($store2)
                            <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                                {{ $store2->address }}<br>
                                {{ $store2->city }}{{ $store2->state?->name ? ', ' . $store2->state->name : '' }} {{ $store2->zip_code }}
                            </p>
                            <a href="#" class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                    uppercase font-semibold text-xs transition-colors">
                                View Store
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                     viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        @endif
                    </div>

                </div>

                {{-- ── Column 4: Search Equipment (CTA) ────────────────── --}}
                <div class="bg-white px-5 py-6 md:px-6 md:py-7 lg:px-8 lg:py-8 flex flex-col       sm:flex-row items-center sm:items-start text-center sm:text-left gap-4">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-yellow-500 flex items-center justify-center">
                        <x-heroicon-o-magnifying-glass class="w-5 h-5 text-gray-900" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            Search Equipment
                        </p>
                        <p class="mt-2 text-lg font-bold text-gray-900 leading-tight">
                            Find What You Need
                        </p>
                        <p class="mt-1.5 text-xs text-gray-600 leading-relaxed">
                            Browse our full inventory and reserve online.
                        </p>
                        <a href="#" class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                uppercase font-semibold text-xs transition-colors">
                            Search
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 shrink-0" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>

            </div>
        </div>

    </div>
</section>
