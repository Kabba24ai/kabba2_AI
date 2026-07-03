{{-- ============================================================
     Contact Us — Question CTA Strip
     Matches approved design: icon on left, heading + description
     in center, large yellow button + phone number on right.
============================================================ --}}

@php
    $cfg   = $section?->content ?? [];
    $phone = $cfg['phone_number'] ?? null;
@endphp

@if($section && $section->status === 'Active')
<section class="py-8">
    <div class="container md:max-w-[720px] lg:max-w-[1140px] 2xl:max-w-[1320px] mx-auto px-4 md:px-0">
        <div class="bg-white rounded-2xl shadow border border-slate-100 px-8 py-6">
            <div class="flex flex-col md:flex-row items-center gap-6">

                {{-- Icon --}}
                <div class="shrink-0 w-14 h-14 rounded-full bg-[#1F1D4E] flex items-center justify-center">
                    <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375m-13.5 3.01c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.184-4.183a1.14 1.14 0 01.778-.332 48.294 48.294 0 005.83-.498c1.585-.233 2.708-1.626 2.708-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/>
                    </svg>
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
                    <a href="{{ $section->button_url ?: ($phone ? 'tel:'.preg_replace('/[^+\d]/', '', $phone) : '#') }}"
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
