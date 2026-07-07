{{-- ============================================================
     Section: Featured Rentals
     Exact same card layout as the live homepage (home/index.blade.php)
     Dynamic via $category_tree (ProductCategory)
============================================================ --}}

<section id="home-v2-featured-categories" class="bg-white pt-4 pb-10 md:pt-6 md:pb-12 lg:pt-8 lg:pb-14">

    <div class="max-w-screen-2xl mx-auto px-4 md:px-6 lg:px-8">

        {{-- ── Section header ──────────────────────────────────────── --}}
        <div class="flex items-center justify-center gap-3 sm:gap-4 md:gap-6 mb-6 sm:mb-8 md:mb-10">
            <div class="flex-1 max-w-[40px] sm:max-w-[80px] md:max-w-[100px] lg:max-w-[120px] h-px bg-gray-300"></div>
            <h2 class="text-lg sm:text-xl md:text-2xl lg:text-[32px]
                       font-semibold uppercase tracking-wide leading-tight
                       text-center whitespace-nowrap">
                {{ $hp->featuredRentals->title }}
            </h2>
            <div class="flex-1 max-w-[40px] sm:max-w-[80px] md:max-w-[100px] lg:max-w-[120px] h-px bg-gray-300"></div>
        </div>

        {{-- ── Category grid — same as live homepage ───────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">

            @foreach ($featuredCategories as $category)

                <div class="border py-5 px-5 md:px-1 lg:py-4 lg:px-4 rounded-lg text-center
                            shadow-[0_2px_5px_rgba(0,0,0,0.05)]
                            transition-transform duration-300 hover:-translate-y-1">

                    <a href="{{ route('front.categories.index', $category->slug) }}"
                       class="transition-all duration-300 ease-in-out hover:text-black">

                        {{-- Image with hover swap --}}
                        <div class="w-full relative group">

                            <img src="{{ $category->image_url }}"
                                 alt="{{ $category->title }}"
                                 class="mx-auto category-product rounded-md object-cover h-auto
                                        transition-opacity duration-500 ease-in-out
                                        group-hover:opacity-0"
                                 loading="lazy" />

                            <img src="{{ $category->hover_image_url }}"
                                 alt="{{ $category->title }} hover"
                                 class="absolute inset-0 w-full h-full object-cover rounded-md
                                        opacity-0 transition-opacity duration-500 ease-in-out
                                        group-hover:opacity-100"
                                 loading="lazy" />

                        </div>

                        {{-- Title --}}
                        <div>
                            <h3 class="mt-4 font-bold text-base
                                       transition-all duration-300 ease-in-out
                                       hover:text-black underline">
                                {{ $category->title }}
                            </h3>
                        </div>

                    </a>

                </div>

            @endforeach

        </div>

    </div>

</section>
