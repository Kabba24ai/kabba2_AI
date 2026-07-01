{{-- ============================================================
     Section: Hero
     Fixed-height compact banner — vertically centered text
     Image position: center 35% keeps the worker's face visible
     No CTA button · lighter overlay for a less dark feel
============================================================ --}}

<section id="home-v2-hero"
         class="relative h-72 md:h-80 lg:h-[450px] overflow-hidden">

    {{-- Background image --}}
    <img
        src="{{ asset('storage/front/images/banner.jpg') }}"
        alt="Equipment rental background"
        class="absolute inset-0 w-full h-full object-cover object-[center_-17%]"
        aria-hidden="true"
    >

    {{-- Gradient overlay: readable left, fades to transparent right --}}
    <div class="absolute inset-0 bg-gradient-to-r from-black/65 via-black/35 to-black/10"></div>

    {{-- Content: vertically centered --}}
    <div class="relative z-10 h-full container mx-auto px-4 sm:px-6 lg:px-8
                flex items-end pb-12 md:pb-16 lg:items-center lg:pb-0">
        <div class="w-full max-w-[620px]">

            <h1 class="text-[28px] sm:text-[36px] lg:text-[35px]
                       font-semibold text-white uppercase leading-[0.95] tracking-tight">
                The Right Equipment.<br>
                The Right Support.
            </h1>

            <p class="mt-3 md:mt-4 text-sm md:text-base text-white/90 leading-relaxed">
                Local team. Quality equipment.<br>
                Ready when you are.
            </p>

        </div>
    </div>

</section>
