{{-- ============================================================
     Section: Hero
     Full-width background image · left gradient overlay ·
     large heading · description · CTA button
     pt-* offsets the fixed navbar (z-9999, ~60px tall)
     pb-* provides enough depth for the contact-strip overlap
============================================================ --}}

<section id="home-v2-hero" class="relative w-full overflow-hidden ">
    <div style="margin-top: 140px;">
    {{-- Background image --}}
    <img
        src="{{ asset('storage/front/images/banner.jpg') }}"
        alt="Equipment rental background"
        class="absolute inset-0 w-full h-full object-top object-cover"
        aria-hidden="true"
    >
    </div>
    {{-- Gradient overlay: dark on the left, fades right --}}
    <div class="absolute inset-0 bg-gradient-to-r from-black/85 via-black/50 to-black/10"></div>

    {{-- Hero content --}}
    <div class="relative z-10">
        <div class="container mx-auto px-6 lg:px-8
                    mt-40 pt-40 pb-28
                    md:pb-36
                    lg:pb-44" >

            {{-- Text column — left half on md+, full width on mobile --}}
            <div class="w-full md:w-7/12 lg:w-1/2 xl:w-5/12">

                {{-- Main heading --}}
                <h1 class="text-4xl sm:text-3xl lg:text-3xl xl:text-3xl
                           font-bold text-white uppercase leading-none tracking-tight">
                    The Right Equipment.<br>
                    The Right Support.
                </h1>

                {{-- Description --}}
                <p class="mt-5 md:mt-6 
                          text-base md:text-lg
                          text-white/85 leading-relaxed">
                    Local team. Quality equipment.<br>
                    Ready when you are.
                </p>

                {{-- CTA button --}}
                <a
                    href="#"
                    class="mt-8 md:mt-10
                           inline-flex items-center gap-2
                           bg-yellow-500 text-grey-400
                           uppercase font-bold
                           text-xs md:text-sm
                           px-5 py-3.5 md:px-9 md:py-4
                           rounded
                           transition-colors
                           focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:ring-offset-2 focus:ring-offset-black mb-5"
                >
                    Browse Equipment
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        class="h-4 w-4 shrink-0"
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

</section>
