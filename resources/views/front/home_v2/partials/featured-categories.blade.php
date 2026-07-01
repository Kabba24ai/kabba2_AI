{{-- ============================================================
     Section: Featured Rental Categories
     8 category cards — 4 col desktop · 2 col tablet · 1 col mobile
     Gradient placeholders swap for real <img> when images are ready.
     When connecting to DB, replace $categories with controller data.
============================================================ --}}

@php
    /**
     * Placeholder data — replace with controller-passed $category_tree when ready.
     * Each 'gradient' value must be a full Tailwind class string so JIT compiles it.
     */
    $categories = [
        ['title' => 'Skid Steer Loaders', 'gradient' => 'from-slate-500 to-slate-800',   'slug' => 'skid-steer-loaders'],
        ['title' => 'Excavators',          'gradient' => 'from-stone-500 to-stone-800',   'slug' => 'excavators'],
        ['title' => 'Mini Skids',          'gradient' => 'from-zinc-500 to-zinc-700',     'slug' => 'mini-skids'],
        ['title' => 'Bulldozers',          'gradient' => 'from-amber-500 to-amber-800',   'slug' => 'bulldozers'],
        ['title' => 'Backhoes',            'gradient' => 'from-orange-500 to-orange-800', 'slug' => 'backhoes'],
        ['title' => 'Rollers',             'gradient' => 'from-green-600 to-green-900',   'slug' => 'rollers'],
        ['title' => 'Telehandlers',        'gradient' => 'from-blue-500 to-blue-800',     'slug' => 'telehandlers'],
        ['title' => 'Boom Lifts',          'gradient' => 'from-sky-500 to-sky-800',       'slug' => 'boom-lifts'],
    ];
@endphp

<section id="home-v2-featured-categories" class="bg-white pt-14 pb-16 md:pt-16 md:pb-20 lg:pt-20 lg:pb-24">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">

        {{-- ── Section header ──────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-3 mb-8 md:mb-10 lg:mb-12">

            <div>
                <p class="text-xs font-semibold text-yellow-500 uppercase tracking-widest mb-2">
                    Browse by Category
                </p>
                <h2 class="text-2xl md:text-3xl lg:text-4xl font-black text-gray-900 uppercase leading-tight">
                    Featured Rental Categories
                </h2>
            </div>

            <a
                href="#"
                class="inline-flex items-center gap-1.5 text-sm font-semibold text-gray-500
                       hover:text-yellow-500 transition-colors shrink-0 group"
            >
                View All Equipment
                <x-heroicon-o-arrow-right class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" />
            </a>

        </div>

        {{-- ── Category grid ───────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-5 lg:gap-5">

            @foreach ($categories as $category)

                {{-- ── Category card ───────────────────────────────── --}}
                <a
                    href="#"
                    class="relative block overflow-hidden rounded-xl aspect-[4/3] group
                           focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2"
                    aria-label="Browse {{ $category['title'] }}"
                >

                    {{-- Placeholder background gradient
                         ── Swap for a real image when ready:
                            <img src="{{ asset('...') }}"
                                 class="absolute inset-0 w-full h-full object-cover
                                        group-hover:scale-110 transition-transform duration-500 ease-in-out"
                                 alt="{{ $category['title'] }}">
                    --}}
                    <div class="absolute inset-0 bg-gradient-to-br {{ $category['gradient'] }}
                                group-hover:scale-110 transition-transform duration-500 ease-in-out">
                    </div>

                    {{-- Bottom fade for text legibility --}}
                    <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/15 to-transparent"></div>

                    {{-- Subtle brightness on hover --}}
                    <div class="absolute inset-0 bg-white/0 group-hover:bg-white/5 transition-colors duration-300"></div>

                    {{-- Card footer: title + arrow ──────────────────── --}}
                    <div class="absolute bottom-0 left-0 right-0 flex items-center justify-between p-5 md:p-5">

                        <h3 class="text-white font-bold text-base leading-snug pr-3">
                            {{ $category['title'] }}
                        </h3>

                        <span
                            class="shrink-0 flex items-center justify-center w-9 h-9 rounded-full
                                   bg-yellow-400 group-hover:bg-yellow-500 transition-colors duration-300"
                            aria-hidden="true"
                        >
                            <x-heroicon-o-arrow-right
                                class="w-4 h-4 text-white
                                       group-hover:translate-x-0.5 transition-transform duration-300"
                            />
                        </span>

                    </div>

                </a>

            @endforeach

        </div>

    </div>
</section>
