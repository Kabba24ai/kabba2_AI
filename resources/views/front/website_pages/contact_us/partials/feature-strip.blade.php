{{-- ============================================================
     Contact Us — Feature Strip
     Dark navy strip with icon + title + subtitle for each feature.
     Reuses same visual design as home page feature strip.
============================================================ --}}

@php
$activeItems = $items->where('status', 'Active')->sortBy('display_order')->values();
$colClass    = match($activeItems->count()) {
    1       => 'grid-cols-1',
    2       => 'grid-cols-1 md:grid-cols-2',
    default => 'grid-cols-1 md:grid-cols-3',
};
@endphp

@if($section && $section->status === 'Active' && $activeItems->isNotEmpty())
<section class="bg-[#1F1D4E] py-8">
    <div class="container mx-auto px-4 md:px-6 lg:px-8">
        <div class="grid {{ $colClass }} gap-0 divide-y md:divide-y-0 md:divide-x divide-white/10">
            @foreach($activeItems as $item)
            @php $icon = $item->icon ?? ''; @endphp
            <div class="flex items-center gap-4 px-6 py-5">
                <div class="shrink-0 w-10 h-10 rounded-full bg-yellow-400 flex items-center justify-center">
                    @if(str_starts_with($icon, 'heroicon-'))
                        <x-dynamic-component :component="$icon" class="w-5 h-5 text-gray-900"/>
                    @elseif(str_starts_with($icon, 'icon-'))
                        <i class="{{ $icon }} text-gray-900 text-base"></i>
                    @else
                        <x-heroicon-o-star class="w-5 h-5 text-gray-900"/>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-bold text-yellow-400 uppercase tracking-widest">{{ $item->title }}</p>
                    @if($item->subtitle)
                    <p class="mt-0.5 text-xs text-white/70">{{ $item->subtitle }}</p>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endif
