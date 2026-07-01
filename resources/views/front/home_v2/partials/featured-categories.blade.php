{{-- ============================================================
     Section: Featured Rentals
     Horizontal cards — image LEFT · title CENTER · chevron RIGHT
     Desktop: 4 cols · Tablet: 2 cols · Mobile: 1 col
     Dynamic via $category_tree (ProductCategory)
============================================================ --}}

<section id="home-v2-featured-categories" class="bg-white py-10 md:py-12 lg:py-14">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">

        {{-- ── Section header: ─── FEATURED RENTALS ─── ──────────── --}}
        <div class="flex items-center justify-center gap-4 md:gap-6 mb-7 md:mb-9">
            <div class="w-30 h-px bg-gray-300"></div>
            <h2 class="mt-1 text-lg md:text-2xl font-semibold tracking-wide leading-none uppercase">
                Featured Rentals
            </h2>
            <div class="w-30 h-px bg-gray-300"></div>
        </div>

        {{-- ── Category grid ───────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 md:gap-4">

            @foreach ($category_tree as $category)

                <a
                    href="{{ route('front.categories.index', $category->slug) }}"
                    class="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-white
                           hover:border-yellow-400 hover:shadow-md transition-all duration-300 group"
                >

                    {{-- Equipment image ─────────────────────────────── --}}
                    <div class="relative shrink-0 w-28 h-20 bg-white flex items-center justify-center overflow-hidden">

                        {{-- Default image --}}
                        <img
                            src="{{ $category->image_url }}"
                            alt="{{ $category->title }}"
                            class="absolute inset-0 w-full h-full object-contain p-1
                                   transition-opacity duration-400 ease-in-out
                                   group-hover:opacity-0"
                            loading="lazy"
                        >

                        {{-- Hover image --}}
                        <img
                            src="{{ $category->hover_image_url }}"
                            alt="{{ $category->title }}"
                            class="absolute inset-0 w-full h-full object-contain p-1
                                   opacity-0 transition-opacity duration-400 ease-in-out
                                   group-hover:opacity-100"
                            loading="lazy"
                        >

                    </div>

                    {{-- Divider line --}}
                    <div class="self-stretch w-px bg-gray-200 shrink-0"></div>

                    {{-- Title ──────────────────────────────────────── --}}
                    <div class="flex-1 px-4 py-3 min-w-0">
                        <h3 class="text-xs font-bold text-gray-900 uppercase tracking-wide leading-snug">
                            {{ $category->title }}
                        </h3>
                    </div>

                    {{-- Chevron ─────────────────────────────────────── --}}
                    <div class="pr-3 shrink-0">
                        <x-heroicon-o-chevron-right
                            class="w-4 h-4 text-gray-900 group-hover:text-yellow-500
                                   group-hover:translate-x-0.5 transition-all duration-300"
                        />
                    </div>

                </a>

            @endforeach

        </div>

    </div>
</section>
