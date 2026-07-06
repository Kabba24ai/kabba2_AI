{{-- ============================================================
     Contact Us — Question CTA Strip
     Matches approved design: icon on left, heading + description
     in center, large yellow button + phone number on right.
============================================================ --}}

@php
    $cfg       = $section?->content ?? [];
    $phone     = $cfg['phone_number'] ?? null;
    $iconClass = $cfg['icon'] ?? 'heroicon-o-chat-bubble-left-ellipsis';
    $isHeroicon = str_starts_with($iconClass, 'heroicon-');
    $isCustom   = str_starts_with($iconClass, 'icon-');
@endphp

@if($section && $section->status === 'Active')
<section class="py-8">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-4 md:px-0">
        <div class="bg-white rounded-2xl shadow border border-slate-100 px-8 py-6">
            <div class="flex flex-col md:flex-row items-center gap-6">

                {{-- Icon --}}
                <div class="shrink-0 w-14 h-14 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                    @if($isHeroicon)
                        <x-dynamic-component :component="$iconClass" class="w-7 h-7 text-white"/>
                    @elseif($isCustom)
                        <i class="{{ $iconClass }} text-2xl text-white"></i>
                    @else
                        <x-heroicon-o-chat-bubble-left-ellipsis class="w-7 h-7 text-white"/>
                    @endif
                </div>

                {{-- Text --}}
                <div class="flex-1 text-center md:text-left">
                    @if($section->title)
                    <p class="text-base font-bold text-gray-900">{{ $section->title }}</p>
                    @endif
                    @if($section->subtitle)
                    <p class="mt-1 text-sm text-gray-500">{{ $section->subtitle }}</p>
                    @endif
                </div>

                {{-- CTA --}}
                <div class="shrink-0 text-center">
                    @if($section->button_text)
                    <a href="{{ $phone ? 'tel:'.preg_replace('/[^+\d]/', '', $phone) : '#' }}"
                       class="inline-block bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-bold text-sm uppercase tracking-wide px-6 py-3 rounded-lg transition-colors shadow-sm">
                        {{ $section->button_text }}
                    </a>
                    @endif
                    @if($phone)
                    <p class="mt-2 text-sm font-semibold text-gray-700">{{ $phone }}</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</section>
@endif
