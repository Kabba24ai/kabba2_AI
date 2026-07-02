{{-- ============================================================
     Section: Feature Strip
     Compact dark-navy bar — icon LEFT · text RIGHT · 3 columns
     Short white vertical separator via absolute positioning
============================================================ --}}

<section id="home-v2-features" class="bg-[#171636]">

    <div class="container mx-auto px-0">

        <div class="grid grid-cols-1 lg:grid-cols-3">

            {{-- Block 1 --}}
            <div class="relative flex items-center
                        gap-4
                        px-5 py-5
                        md:px-6 md:py-6
                        lg:px-8 lg:py-6
                        border-b border-white/20
                        lg:border-b-0">

                <x-heroicon-o-shield-check
                    class="w-8 h-8 md:w-9 md:h-9 lg:w-10 lg:h-10 shrink-0 text-yellow-400" />

                <div class="min-w-0">
                    <p class="text-white font-bold
                              text-xs md:text-xs
                              uppercase tracking-wider leading-snug">
                        Well Maintained Equipment
                    </p>

                    <p class="text-gray-400
                              text-xs md:text-xs
                              mt-0.5 leading-snug">
                        Reliable. Clean. Job Ready.
                    </p>
                </div>

                {{-- Desktop separator --}}
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center">
                    <span class="h-8 w-px bg-white"></span>
                </span>

            </div>

            {{-- Block 2 --}}
            <div class="relative flex items-center
                        gap-4
                        px-5 py-5
                        md:px-6 md:py-6
                        lg:px-8 lg:py-6
                        border-b border-white/20
                        lg:border-b-0">

                <i class="fa-solid fa-headset
                          text-yellow-400
                          text-3xl md:text-4xl
                          w-8 md:w-10
                          text-center shrink-0"></i>

                <div class="min-w-0">
                    <p class="text-white font-bold
                              text-xs md:text-xs
                              uppercase tracking-wider leading-snug">
                        Expert Local Support
                    </p>

                    <p class="text-gray-400
                              text-xs md:text-xs
                              mt-0.5 leading-snug">
                        Real people. Real answers.
                    </p>
                </div>

                {{-- Desktop separator --}}
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center">
                    <span class="h-8 w-px bg-white"></span>
                </span>

            </div>

            {{-- Block 3 --}}
            <div class="flex items-center
                        gap-4
                        px-5 py-5
                        md:px-6 md:py-6
                        lg:px-8 lg:py-6">

                <x-heroicon-o-truck
                    class="w-8 h-8 md:w-9 md:h-9 lg:w-10 lg:h-10 shrink-0 text-yellow-400" />

                <div class="min-w-0">
                    <p class="text-white font-bold
                              text-xs md:text-xs
                              uppercase tracking-wider leading-snug">
                        Delivery Available
                    </p>

                    <p class="text-gray-400
                              text-xs md:text-xs
                              mt-0.5 leading-snug">
                        Fast delivery to your job site.
                    </p>
                </div>

            </div>

        </div>

    </div>

</section>
