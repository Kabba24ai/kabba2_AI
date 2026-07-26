@props([
    'label',
    'value',
    'href'   => null,
    'accent' => 'gray',   // gray | orange | rose | amber | green | blue
    'hint'   => null,
])

@php
    // Identical stat-card presentation shared by the Billing Operations
    // surfaces. A card links to its workspace when $href is provided.
    $accents = [
        'gray'   => 'border-gray-200 bg-gray-50 text-gray-700',
        'orange' => 'border-orange-200 bg-orange-50/60 text-orange-700',
        'rose'   => 'border-rose-200 bg-rose-50/60 text-rose-700',
        'amber'  => 'border-amber-200 bg-amber-50/60 text-amber-700',
        'green'  => 'border-green-200 bg-green-50/60 text-green-700',
        'blue'   => 'border-blue-200 bg-blue-50/60 text-blue-700',
    ];
    $accentClasses = $accents[$accent] ?? $accents['gray'];
@endphp

@if ($href)
    <a href="{{ $href }}"
       {{ $attributes->merge(['class' => 'block rounded-xl border p-4 transition hover:shadow-md ' . $accentClasses]) }}>
        <div class="text-2xl font-bold">{{ $value }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">{{ $label }}</div>
        @if ($hint)
            <div class="text-[11px] text-gray-400 mt-1">{{ $hint }}</div>
        @endif
    </a>
@else
    <div {{ $attributes->merge(['class' => 'rounded-xl border p-4 ' . $accentClasses]) }}>
        <div class="text-2xl font-bold">{{ $value }}</div>
        <div class="text-xs font-medium text-gray-600 mt-1">{{ $label }}</div>
        @if ($hint)
            <div class="text-[11px] text-gray-400 mt-1">{{ $hint }}</div>
        @endif
    </div>
@endif
