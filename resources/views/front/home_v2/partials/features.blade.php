{{-- ============================================================
     Section: Feature Strip
     Compact dark-navy bar — icon LEFT · text RIGHT · 3 columns
     Short white vertical separator via absolute positioning
============================================================ --}}

<section id="home-v2-features" class="bg-[#171636]">

    <div class="container mx-auto px-0">

        <div class="grid grid-cols-1 lg:grid-cols-3">

            {{-- ── Block 1: Well Maintained Equipment ─────────────── --}}
            <div class="relative bg-[#171636] flex items-center gap-4 px-6 py-5 lg:px-8 lg:py-6">

                <x-heroicon-o-shield-check class="w-10 h-10 shrink-0 text-yellow-400" />

                <div class="min-w-0">
                    <p class="text-white font-bold text-xs uppercase tracking-wider leading-snug">
                        Well Maintained Equipment
                    </p>
                    <p class="text-gray-400 text-xs mt-0.5 leading-snug">
                        Reliable. Clean. Job Ready.
                    </p>
                </div>

                {{-- Short white separator (desktop only) --}}
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center pointer-events-none">
                    <span class="h-7 w-px bg-white"></span>
                </span>

            </div>

            {{-- ── Block 2: Expert Local Support ──────────────────── --}}
            <div class="relative bg-[#171636] flex items-center gap-4 px-6 py-5 lg:px-8 lg:py-6">

                <i class="fa-solid fa-headset text-yellow-400 text-4xl shrink-0 w-10 text-center"></i>

                <div class="min-w-0">
                    <p class="text-white font-bold text-xs uppercase tracking-wider leading-snug">
                        Expert Local Support
                    </p>
                    <p class="text-gray-400 text-xs mt-0.5 leading-snug">
                        Real people. Real answers.
                    </p>
                </div>

                {{-- Short white separator (desktop only) --}}
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center pointer-events-none">
                    <span class="h-7 w-px bg-white"></span>
                </span>

            </div>

            {{-- ── Block 3: Delivery Available — no separator after last ── --}}
            <div class="bg-[#171636] flex items-center gap-4 px-6 py-5 lg:px-8 lg:py-6">

                <x-heroicon-o-truck class="w-10 h-10 shrink-0 text-yellow-400" />

                <div class="min-w-0">
                    <p class="text-white font-bold text-xs uppercase tracking-wider leading-snug">
                        Delivery Available
                    </p>
                    <p class="text-gray-400 text-xs mt-0.5 leading-snug">
                        Fast delivery to your job site.
                    </p>
                </div>

            </div>

        </div>
    </div>

</section>
