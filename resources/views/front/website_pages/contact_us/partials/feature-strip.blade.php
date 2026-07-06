{{-- ============================================================
     Contact Us — Feature Strip
     Reuses identical design as home v2 feature strip.
============================================================ --}}

@php
$activeItems = $items->where('status', 'Active')->sortBy('display_order')->values();
$colClass    = match($activeItems->count()) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-1 lg:grid-cols-2',
    default => 'grid-cols-1 lg:grid-cols-3',
};
@endphp

@if($section && $section->status === 'Active' && $activeItems->isNotEmpty())
<section class="bg-[#171636]">
    <div class="container mx-auto px-0">
        <div class="grid {{ $colClass }}">

            @foreach($activeItems as $item)
            <div class="relative flex items-center
                        gap-4
                        px-5 py-5
                        md:px-6 md:py-6
                        lg:px-8 lg:py-6
                        {{ !$loop->last ? 'border-b border-white/20 lg:border-b-0' : '' }}">

                @php $icon = $item->icon ?? ''; @endphp

                @if(str_starts_with($icon, 'heroicon-') || str_starts_with($icon, 'icon-'))
                    <x-dynamic-component :component="$icon"
                        class="w-8 h-8 md:w-9 md:h-9 lg:w-10 lg:h-10 shrink-0 text-yellow-400" />
                @else
                    <i class="fa-solid {{ $icon }} text-yellow-400 text-3xl md:text-4xl w-8 md:w-10 text-center shrink-0"></i>
                @endif

                <div class="min-w-0">
                    <p class="text-white font-bold text-xs md:text-xs uppercase tracking-wider leading-snug">
                        {{ $item->title }}
                    </p>
                    @if($item->subtitle)
                    <p class="text-gray-400 text-xs md:text-xs mt-0.5 leading-snug">
                        {{ $item->subtitle }}
                    </p>
                    @endif
                </div>

                @if(!$loop->last)
                <span class="hidden lg:flex absolute right-0 top-0 bottom-0 items-center">
                    <span class="h-8 w-px bg-white"></span>
                </span>
                @endif

            </div>
            @endforeach

        </div>
    </div>
</section>
@endif
