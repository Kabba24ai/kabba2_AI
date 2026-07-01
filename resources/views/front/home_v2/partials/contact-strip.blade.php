{{-- ============================================================
     Section: Contact Strip
     White card floated over the hero's bottom edge.
     Desktop: 4 cols · Tablet: 2×2 · Mobile: stacked
     gap-px + bg-gray-100 pattern = 1px dividers between cells.
     -mt-* must be ≤ hero pb-* at each breakpoint.
============================================================ --}}

<section id="home-v2-contact-strip" class="relative z-20 pb-2 md:pb-0 mb-5">
    <div class="container mx-auto px-4 md:px-6 lg:px-8
                -mt-12 md:-mt-16 lg:-mt-20">
        <div class="text-center uppercase text-[#171636] mt-6 mb-6">
            <p class="text-sm md:text-sm font-semibold tracking-wider">
                Need Help Finding The Right Equipment?
            </p>

            <h2 class="mt-1 text-lg md:text-2xl font-semibold  tracking-wide leading-none">
                Call Our Rental Specialists
            </h2>
        </div>
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">

            {{-- gap-px on the grid + bg-gray-100 = 1px cell dividers --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-px bg-gray-100">

                {{-- ── Column 1: Main Sales Line ──────────────────────── --}}
                <div class="bg-white px-6 py-8 lg:px-8 flex items-start gap-4 border-r border-gray-200">
                    
                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-o-phone class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            Main Sales Line
                        </p>
                        <a href="tel:+15551234567"
                           class="mt-2 block text-lg font-bold text-yellow-500 leading-tight hover:text-yellow-500 transition-colors">
                            (555) 123-4567
                        </a>
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            Mon – Fri, 8 am – 6 pm
                        </p>
                    </div>

                </div>

                {{-- ── Column 2: Store Location ────────────────────────── --}}
                <div class="bg-white px-6 py-8 lg:px-8 flex items-start gap-4 border-r border-gray-200">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-o-map-pin class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            Store Location
                        </p>
                        <a href="tel:+15551234568"
                           class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                            (555) 123-4568
                        </a>
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            123 Main Street<br>
                            Springfield, IL 62701
                        </p>
                        <a href="#" class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                uppercase font-semibold
                                text-xs md:text-xs
                                transition-colors"
                        >
                            View Store
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="3"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>

                {{-- ── Column 3: Second Store ──────────────────────────── --}}
                <div class="bg-white px-6 py-8 lg:px-8 flex items-start gap-4 border-r border-gray-200">

                    <div class="shrink-0 mt-0.5 w-11 h-11 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                        <x-heroicon-o-building-storefront class="w-5 h-5 text-white" />
                    </div>

                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-gray-900 uppercase tracking-widest leading-none">
                            Second Store
                        </p>
                        <a href="tel:+15551234569"
                           class="mt-2 block text-lg font-bold text-gray-900 leading-tight hover:text-yellow-500 transition-colors">
                            (555) 123-4569
                        </a>
                        <p class="mt-1.5 text-xs text-gray-500 leading-relaxed">
                            456 Oak Avenue<br>
                            Springfield, IL 62702
                        </p>
                        <a href="#" class="mt-1 inline-flex items-center gap-1 text-yellow-500
                                uppercase font-semibold
                                text-xs md:text-xs
                                transition-colors"
                        >
                            View Store
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="3"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>

                {{-- ── Column 4: Search Equipment (CTA) ────────────────── --}}
                <div class="bg-white px-6 py-8 lg:px-8 flex items-start gap-4">

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
                                uppercase font-semibold
                                text-xs md:text-xs
                                transition-colors"
                        >
                            Search
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-3 w-3 shrink-0"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                stroke-width="3"
                                aria-hidden="true"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>

                </div>

            </div>
        </div>

    </div>
</section>
