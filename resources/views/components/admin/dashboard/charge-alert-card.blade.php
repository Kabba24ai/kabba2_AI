@props([
    'title' => '',
    'subtitle' => '',
    'iconGradient' => 'from-orange-500 to-orange-600',
    'accentHex' => '#f97316',      // donut Outstanding segment color
    'chartId' => 'charge-donut',
    'href' => '#',
    'actionLabel' => 'View',
    'actionClass' => 'bg-orange-500 hover:bg-orange-600',
    'outstanding' => 0,
    'completedToday' => 0,
    'newToday' => 0,
    'avgAgeDays' => 0,
])

{{--
    Charge Alerts donut card (Dashboard V2 Phase 1B refinement).
    The donut (Outstanding vs Completed Today) is the dominant visual; the
    center shows the Outstanding count (the primary operational concern).
    New Today / Average Age are secondary. Chart canvas is wire:ignore and is
    (re)drawn/updated by JS on the 'charge-alerts-updated' Livewire event;
    Blade-rendered numbers refresh naturally on the 30s poll.
--}}

<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 flex flex-col h-full hover:shadow-lg transition-all">

    {{-- Header --}}
    <div class="flex items-center mb-4">
        <div class="p-2.5 rounded-lg bg-gradient-to-br {{ $iconGradient }} mr-3">
            {{ $icon }}
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
            <p class="text-sm text-gray-600">{{ $subtitle }}</p>
        </div>
    </div>

    {{-- Donut (dominant visual) with centered Outstanding count --}}
    <div class="relative flex items-center justify-center" style="min-height: 210px;">
        <div id="{{ $chartId }}" wire:ignore
             data-outstanding="{{ $outstanding }}"
             data-completed="{{ $completedToday }}"
             data-color="{{ $accentHex }}"
             class="w-full flex justify-center"></div>
        <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none">
            <span class="text-4xl font-bold text-gray-900 leading-none">{{ $outstanding }}</span>
            <span class="text-xs font-medium tracking-wide text-gray-500 uppercase mt-1">Outstanding</span>
            @if($outstanding === 0 && $completedToday === 0)
                <span class="text-[11px] text-gray-400 mt-1">No Active Alerts</span>
            @endif
        </div>
    </div>

    {{-- Legend --}}
    <div class="flex items-center justify-center gap-5 mt-3 text-sm">
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block w-2.5 h-2.5 rounded-full" style="background: {{ $accentHex }}"></span>
            <span class="text-gray-600">Outstanding</span>
            <span class="font-semibold text-gray-900">{{ $outstanding }}</span>
        </span>
        <span class="inline-flex items-center gap-1.5">
            <span class="inline-block w-2.5 h-2.5 rounded-full bg-gray-300"></span>
            <span class="text-gray-600">Completed Today</span>
            <span class="font-semibold text-gray-900">{{ $completedToday }}</span>
        </span>
    </div>

    {{-- Secondary metrics --}}
    <div class="grid grid-cols-2 gap-3 mt-4">
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
            <div class="text-[11px] font-medium tracking-wide text-gray-500 uppercase">New Today</div>
            <div class="text-lg font-bold text-gray-900">{{ $newToday }}</div>
        </div>
        <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
            <div class="text-[11px] font-medium tracking-wide text-gray-500 uppercase">Average Age</div>
            <div class="text-lg font-bold text-gray-900">
                {{ $avgAgeDays }} <span class="text-xs font-medium text-gray-500">{{ $avgAgeDays === 1 ? 'day' : 'days' }}</span>
            </div>
        </div>
    </div>

    {{-- Primary action, bottom-anchored --}}
    <a href="{{ $href }}" class="mt-auto pt-5 block">
        <span class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition-colors {{ $actionClass }}">
            {{ $actionLabel }}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </a>
</div>
