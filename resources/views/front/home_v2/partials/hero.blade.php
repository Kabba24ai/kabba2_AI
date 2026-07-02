{{-- ============================================================
     Section: Hero
     Fixed-height compact banner — vertically centered text
     Image position: center 35% keeps the worker's face visible
     No CTA button · lighter overlay for a less dark feel
============================================================ --}}

<section id="home-v2-hero" class="relative pt-15 h-[280px] sm:h-[340px] md:h-[400px] lg:h-[450px] overflow-hidden">

    {{-- Background image --}}
    <img
        src="{{ asset('storage/front/images/banner.jpg') }}"
        alt="Equipment rental background"
        class="absolute inset-0 w-full h-full object-cover mt-[60px] sm:mt-[70px] md:mt-[60px] lg:mt-0
               object-[center_top] sm:object-[center_20%] md:object-[center_30%] lg:object-[center_-17%]"
        aria-hidden="true"
    >

    {{-- Gradient overlay: readable left, fades to transparent right --}}
    <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/35 to-black/10"></div>

    {{-- Content: vertically centered --}}
    <div class="relative z-10 h-full container mx-auto
               px-5 sm:px-6 lg:px-8
               flex items-center pb-8
               sm:pb-10
               md:pb-12
               lg:items-center lg:pb-0">
        <div class="max-w-full sm:max-w-[520px] lg:max-w-[620px]">

            <h1 class="text-[28px] leading-tight
                       sm:text-[34px]
                       md:text-[42px]
                       lg:text-[35px]
                       font-semibold uppercase text-white">
                The Right Equipment.<br>
                The Right Support.
            </h1>

            <p class="mt-3
                       text-sm
                       sm:text-base
                       md:text-lg
                       lg:text-base
                       leading-relaxed text-white/90 " >
                Local team. Quality equipment.<br>
                Ready when you are.
            </p>

        </div>
    </div>

</section>
