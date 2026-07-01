{{-- ============================================================
     Section: Featured Rentals
     Vertical cards — image TOP · title + chevron BOTTOM
     Hover: image-swap opacity fade (identical to live homepage)
     Desktop: 4 cols · Tablet: 2 cols · Mobile: 1 col
     Dynamic via $category_tree (ProductCategory)
============================================================ --}}

<section id="home-v2-featured-categories" class="bg-white py-10 md:py-12 lg:py-14">

    {{-- Wider container to give 4 large cards room to breathe --}}
    <div class="max-w-screen-2xl mx-auto px-4 md:px-6 lg:px-8">

        {{-- ── Section header: ─── FEATURED RENTALS ─── ──────────── --}}
        <div class="flex items-center justify-center gap-4 md:gap-6 mb-7 md:mb-9">
            <div class="w-24 h-px bg-gray-300"></div>
            <h2 class="mt-1 text-lg md:text-2xl font-semibold tracking-wide leading-none uppercase">
                Featured Rentals
            </h2>
            <div class="w-24 h-px bg-gray-300"></div>
        </div>

        {{-- ── Category grid ───────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 md:gap-6">

            @foreach ($category_tree as $category)

                {{-- ── Card ─────────────────────────────────────────── --}}
                {{--
                    Hover reused from live homepage (home/index.blade.php):
                      • group on the card
                      • default img: group-hover:opacity-0 · transition-opacity duration-500
                      • hover  img: opacity-0 group-hover:opacity-100 · transition-opacity duration-500
                      • card lift: hover:-translate-y-1 transition-transform duration-300
                --}}
                <a
                    href="{{ route('front.categories.index', $category->slug) }}"
                    class="block bg-white border border-gray-200 rounded-xl overflow-hidden
                           shadow-[0_2px_5px_rgba(0,0,0,0.05)]
                           transition-transform duration-300 hover:-translate-y-1 hover:shadow-md
                           group"
                >

                    {{-- ── Image area ───────────────────────────────── --}}
                    <div class="relative w-full aspect-[4/3] bg-white overflow-hidden">

                        {{-- Default image (fades out on hover) --}}
                        <img
                            src="{{ $category->image_url }}"
                            alt="{{ $category->title }}"
                            class="absolute inset-0 w-full h-full object-contain
                                   transition-opacity duration-500 ease-in-out
                                   group-hover:opacity-0"
                            loading="lazy"
                        >

                        {{-- Hover image (fades in on hover) --}}
                        <img
                            src="{{ $category->hover_image_url }}"
                            alt="{{ $category->title }} hover"
                            class="absolute inset-0 w-full h-full object-contain
                                   opacity-0 transition-opacity duration-500 ease-in-out
                                   group-hover:opacity-100"
                            loading="lazy"
                        >

                    </div>

                    {{-- ── Card footer: title + chevron ─────────────── --}}
                    <div class="border-t border-gray-100 px-5 py-4
                                flex items-center justify-between gap-3">

                        <h3 class="font-bold text-sm text-gray-900 uppercase
                                   tracking-wide leading-snug">
                            {{ $category->title }}
                        </h3>

                        <x-heroicon-o-chevron-right
                            class="w-4 h-4 shrink-0 text-gray-900
                                   group-hover:text-yellow-500
                                   group-hover:translate-x-0.5
                                   transition-all duration-300"
                        />

                    </div>

                </a>

            @endforeach

        </div>

    </div>
</section>
