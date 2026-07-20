@props([
    // 'checklist' | 'manual' — or null to render nothing. Origin is
    // informational ONLY (approved architecture rule): it never gates
    // which actions render. Pass a title for the surface detail when the
    // stored data proves it (BillingChargePresenter::originForCharge).
    'origin' => null,
    'title'  => null,
])

@php
    $originStyles = [
        'checklist' => ['label' => 'Checklist', 'classes' => 'bg-indigo-50 text-indigo-700 border-indigo-200'],
        'manual'    => ['label' => 'Manual',    'classes' => 'bg-blue-50 text-blue-700 border-blue-200'],
    ];
    $style = $originStyles[$origin] ?? null;
@endphp

@if ($style)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium border ' . $style['classes']]) }}
          @if ($title) title="{{ $title }}" @endif>
        {{ $style['label'] }}
    </span>
@endif
