{{-- ============================================================
     Section: Feature Strip
     Four trust/value blocks — icon · title · description
     Desktop: 4 cols + dividers · Tablet: 2 cols · Mobile: stacked
     Dark bg creates visual contrast after the white categories section.
============================================================ --}}

<section id="home-v2-features" class="bg-gray-900 py-14 md:py-16 lg:py-20">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4
                    gap-8 md:gap-10
                    lg:gap-0 lg:divide-x lg:divide-gray-700/40">

            {{-- ── Block 1: Well Maintained Equipment ─────────────── --}}
            <div class="flex flex-col lg:pr-10">

                <div class="w-12 h-12 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-4 shrink-0">
                    <x-heroicon-o-wrench-screwdriver class="w-6 h-6 text-yellow-400" />
                </div>

                <h3 class="text-white font-bold text-base lg:text-lg mb-2 leading-snug">
                    Well Maintained Equipment
                </h3>

                <p class="text-gray-400 text-sm leading-relaxed">
                    Every machine in our fleet is regularly inspected, serviced, and ready to perform when you need it most.
                </p>

            </div>

            {{-- ── Block 2: Expert Local Support ──────────────────── --}}
            <div class="flex flex-col lg:px-10">

                <div class="w-12 h-12 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-4 shrink-0">
                    <x-heroicon-o-user-group class="w-6 h-6 text-yellow-400" />
                </div>

                <h3 class="text-white font-bold text-base lg:text-lg mb-2 leading-snug">
                    Expert Local Support
                </h3>

                <p class="text-gray-400 text-sm leading-relaxed">
                    Our local team knows the region and the gear. We're here to help before, during, and after your rental.
                </p>

            </div>

            {{-- ── Block 3: Delivery Available ─────────────────────── --}}
            <div class="flex flex-col lg:px-10">

                <div class="w-12 h-12 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-4 shrink-0">
                    <x-heroicon-o-truck class="w-6 h-6 text-yellow-400" />
                </div>

                <h3 class="text-white font-bold text-base lg:text-lg mb-2 leading-snug">
                    Delivery Available
                </h3>

                <p class="text-gray-400 text-sm leading-relaxed">
                    We deliver equipment directly to your job site — fast, reliable, and always on your schedule.
                </p>

            </div>

            {{-- ── Block 4: Large Inventory ────────────────────────── --}}
            <div class="flex flex-col lg:pl-10">

                <div class="w-12 h-12 rounded-xl bg-yellow-400/15 flex items-center justify-center mb-4 shrink-0">
                    <x-heroicon-o-squares-2x2 class="w-6 h-6 text-yellow-400" />
                </div>

                <h3 class="text-white font-bold text-base lg:text-lg mb-2 leading-snug">
                    Large Inventory
                </h3>

                <p class="text-gray-400 text-sm leading-relaxed">
                    From compact mini skids to heavy excavators, our broad fleet means the right machine is always in reach.
                </p>

            </div>

        </div>
    </div>
</section>
