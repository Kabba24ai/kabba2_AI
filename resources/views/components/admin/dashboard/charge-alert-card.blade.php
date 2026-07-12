@props([
    'title' => '',
    'subtitle' => '',
    'iconGradient' => 'from-orange-500 to-orange-600',
    'href' => '#',
    'actionLabel' => 'View',
    'actionClass' => 'bg-orange-500 hover:bg-orange-600',
    // Metrics. resolved === null renders the "unavailable" state (not zero).
    'outstanding' => 0,
    'resolved' => null,
    'newToday' => 0,
    'avgAgeDays' => 0,
])

{{--
    Charge Alerts action card (Dashboard V2 Phase 1B).
    Summary-and-direct: four metrics in a 2x2 grid over a bottom-anchored
    primary action. Equal height across cards via flex/h-full + mt-auto button.
    'Resolved' shows an explicit unavailable state — never a misleading 0 —
    because reliable terminal-transition tracking does not yet exist.
--}}

<div class="bg-white rounded-xl shadow-sm p-6 border border-gray-300 flex flex-col h-full hover:shadow-lg transition-all">

    {{-- Header --}}
    <div class="flex items-center mb-5">
        <div class="p-2.5 rounded-lg bg-gradient-to-br {{ $iconGradient }} mr-3">
            {{ $icon }}
        </div>
        <div>
            <h3 class="text-lg font-bold text-gray-900">{{ $title }}</h3>
            <p class="text-sm text-gray-600">{{ $subtitle }}</p>
        </div>
    </div>

    {{-- 2x2 metric grid --}}
    <div class="grid grid-cols-2 gap-4">
        {{-- Outstanding --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">Outstanding</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $outstanding }}</div>
        </div>

        {{-- Resolved (unavailable — Phase 2 lifecycle tracking) --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">Resolved</div>
            @if(is_null($resolved))
                <div class="mt-1 text-2xl font-bold text-gray-300 leading-none" title="Tracking not yet available">&mdash;</div>
                <div class="text-[11px] text-gray-400 mt-1 leading-tight">Tracking not yet available</div>
            @else
                <div class="mt-1 text-2xl font-bold text-gray-900">{{ $resolved }}</div>
            @endif
        </div>

        {{-- New Today --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">New Today</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">{{ $newToday }}</div>
        </div>

        {{-- Average Age --}}
        <div class="rounded-lg border border-gray-200 bg-gray-50 p-4">
            <div class="text-xs font-medium tracking-wide text-gray-500 uppercase">Average Age</div>
            <div class="mt-1 text-2xl font-bold text-gray-900">
                {{ $avgAgeDays }} <span class="text-sm font-medium text-gray-500">{{ $avgAgeDays === 1 ? 'day' : 'days' }}</span>
            </div>
        </div>
    </div>

    {{-- Primary action, anchored to the bottom --}}
    <a href="{{ $href }}"
       class="mt-auto pt-5 block">
        <span class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-white text-sm font-semibold shadow-sm transition-colors {{ $actionClass }}">
            {{ $actionLabel }}
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
            </svg>
        </span>
    </a>
</div>
