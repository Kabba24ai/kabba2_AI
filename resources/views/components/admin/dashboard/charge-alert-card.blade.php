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
    'resolvedThisWeek' => 0,
    'newToday' => 0,
    'avgAgeDays' => 0,
])

{{--
    Charge Alerts donut card (Dashboard V2 Phase 1B final UI refinement).
    Two-column executive summary: LEFT = dominant donut with the centered
    Outstanding count (no legend beneath it); RIGHT = a 2x2 KPI grid. A compact,
    centered, fixed-width action button is anchored at the bottom. Chart canvas
    is wire:ignore and (re)drawn/updated by JS on the 'charge-alerts-updated'
    event; Blade numbers refresh on the 30s poll. Presentation only.
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

    {{-- Two columns: donut (left) + 2x2 KPI grid (right) --}}
    <div class="flex-1 flex flex-col sm:flex-row items-center gap-6">

        {{-- Left: dominant donut with centered Outstanding count (no legend) --}}
        <div class="relative flex items-center justify-center w-full sm:w-1/2" style="min-height: 210px;">
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

        {{-- Right: 2x2 KPI grid --}}
        <div class="w-full sm:w-1/2 grid grid-cols-2 gap-3">
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
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
                <div class="text-[11px] font-medium tracking-wide text-gray-500 uppercase">Completed Today</div>
                <div class="text-lg font-bold text-gray-900">{{ $completedToday }}</div>
            </div>
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-center">
                <div class="text-[11px] font-medium tracking-wide text-gray-500 uppercase">Resolved This Week</div>
                <div class="text-lg font-bold text-gray-900">{{ $resolvedThisWeek }}</div>
            </div>
        </div>
    </div>

    {{-- Primary action: compact, centered, identical fixed width on both cards --}}
    <div class="mt-5 flex justify-center">
        <a href="{{ $href }}"
           class="w-52 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition-colors {{ $actionClass }}">
            {{ $actionLabel }}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4 flex-shrink-0">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </a>
    </div>
</div>
