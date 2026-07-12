@props([
    // Destination: pass EITHER an href (renders an <a>) OR an onclick (renders a <div>).
    'href' => null,
    'onclick' => null,

    // Header
    'title' => '',
    'titleClass' => 'text-gray-800',
    'iconGradient' => 'from-blue-500 to-blue-600',

    // Card chrome
    'borderClass' => 'border-gray-300',
    'hoverBorderClass' => 'hover:border-blue-400',

    // Two-stat row (left = due/start, right = completed). Provide a `right`
    // slot to render custom content on the right (e.g. "Action Required").
    'leftValue' => null,
    'leftValueClass' => 'text-gray-900',
    'leftLabel' => '',
    'rightValue' => null,
    'rightValueClass' => 'text-green-600',
    'rightLabel' => '',
])

{{--
    Standardized Operations Overview card (Dashboard V2 Phase 1).
    A single shared shell for all eight operational cards: identical padding,
    radius, shadow, hover, icon badge, title, stat row, and footer container.
    Presentation only — every count, route, and calculation is supplied by the
    caller and passed straight through unchanged.
--}}

@php
    $tag = $href ? 'a' : 'div';
    $cardClasses = "group bg-white rounded-xl shadow-sm border {$borderClass} p-5 flex flex-col h-full cursor-pointer hover:shadow-lg {$hoverBorderClass} transition-all";
@endphp

<{{ $tag }}
    @if($href) href="{{ $href }}" @endif
    @if($onclick) onclick="{{ $onclick }}" @endif
    {{ $attributes->merge(['class' => $cardClasses]) }}>

    {{-- Icon badge --}}
    <div class="flex items-center justify-between mb-4">
        <div class="p-2.5 rounded-lg bg-gradient-to-br {{ $iconGradient }} group-hover:scale-110 transition-transform duration-200">
            {{ $icon }}
        </div>
    </div>

    {{-- Title --}}
    <h3 class="text-sm font-semibold {{ $titleClass }} mb-4 leading-tight">{{ $title }}</h3>

    {{-- Stat row --}}
    <div class="flex items-center flex-1">
        <div class="flex-1 flex flex-col items-center justify-center">
            <div class="text-2xl font-bold {{ $leftValueClass }} mb-1">{{ $leftValue }}</div>
            <div class="text-xs text-gray-500 font-medium tracking-wide text-center">{{ $leftLabel }}</div>
        </div>
        <div class="h-12 w-px bg-gray-300"></div>
        <div class="flex-1 flex flex-col items-center justify-center">
            @isset($right)
                {{ $right }}
            @else
                <div class="text-2xl font-bold {{ $rightValueClass }} mb-1">{{ $rightValue }}</div>
                <div class="text-xs text-gray-500 font-medium tracking-wide text-center">{{ $rightLabel }}</div>
            @endisset
        </div>
    </div>

    {{-- Standardized footer --}}
    <div class="mt-4 pt-3 border-t border-gray-300">
        {{ $slot }}
    </div>
</{{ $tag }}>
