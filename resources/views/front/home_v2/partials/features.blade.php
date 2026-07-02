{{-- ============================================================
     Section: Feature Strip
     Compact dark-navy bar — icon LEFT · text RIGHT · 3 columns
     Short white vertical separator via absolute positioning
============================================================ --}}

<section id="home-v2-features" class="bg-[#171636]">

    <div class="container mx-auto px-0">

        <div class="grid grid-cols-1 lg:grid-cols-3">

            @foreach($hp->featureStrip->items as $item)

            <div class="relative flex items-center
                        gap-4
                        px-5 py-5
                        md:px-6 md:py-6
                        lg:px-8 lg:py-6
                        {{ !$loop->last ? 'border-b border-white/20 lg:border-b-0' : '' }}">

                @if(str_starts_with($item->icon ?? '', 'heroicon-'))
                    <x-dynamic-component :component="$item->icon"
                        class="w-8 h-8 md:w-9 md:h-9 lg:w-10 lg:h-10 shrink-0 text-yellow-400" />
                @else
                    <i class="fa-solid {{ $item->icon }} text-yellow-400 text-3xl md:text-4xl w-8 md:w-10 text-center shrink-0"></i>
                @endif

                <div class="min-w-0">
                    <p class="text-white font-bold
                              text-xs md:text-xs
                              uppercase tracking-wider leading-snug">
                        {{ $item->title }}
                    </p>

                    <p class="text-gray-400
                              text-xs md:text-xs
                              mt-0.5 leading-snug">
                        {{ $item->subtitle }}
                    </p>
                </div>

                @if(!$loop->last)
                {{-- Desktop separator --}}
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center">
                    <span class="h-8 w-px bg-white"></span>
                </span>
                @endif

            </div>

            @endforeach

        </div>

    </div>

</section>
