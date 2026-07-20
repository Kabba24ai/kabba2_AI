{{-- Wall-board summary strip (Phase 4 §7): remaining-work-at-a-glance.
     Counts come from Board::summarize() — the already-loaded collection,
     never independent queries — so they always match the cards below and
     respect the store filter. Never color-only: every tile carries its
     label; the actionable tiles also carry an icon. --}}
@php
    $tiles = [
        // key, label, icon, tone-when-nonzero (workflow first, then urgency,
        // then live warnings — mirrors the board's section order)
        ['pending', 'Pending', 'wrench', 'bg-sky-700 text-white border-sky-800'],
        ['ready', 'Staged', 'check', 'bg-green-600 text-white border-green-700'],
        ['delivered', 'Delivered Today', null, 'bg-gray-200 text-gray-700 border-gray-300'],
        ['rush', 'Rush', 'bolt', 'bg-red-600 text-white border-red-700'],
        ['fuelNotVerified', 'Fuel Not Verified', 'fire', 'bg-amber-500 text-white border-amber-600'],
        ['needsEquipment', 'Needs Equipment', 'wrench', 'bg-slate-700 text-white border-slate-800'],
        ['overdue', 'Overdue', 'exclamation-triangle', 'bg-orange-500 text-white border-orange-600'],
        ['today', 'Due Today', null, 'bg-sky-600 text-white border-sky-700'],
        ['tomorrow', 'Due Tomorrow', null, 'bg-white text-gray-700 border-gray-300'],
        ['alternate', 'Substitute', null, 'bg-purple-700 text-white border-purple-800'],
        ['unknown', 'Confirm Match', null, 'bg-slate-600 text-white border-slate-700'],
        ['maintenanceHold', 'Maintenance Hold', null, 'bg-amber-100 text-amber-900 border-amber-300'],
        ['damaged', 'Damaged', null, 'bg-red-100 text-red-900 border-red-300'],
    ];
@endphp
<div class="mb-6 flex flex-wrap items-stretch gap-2 wall4k:gap-3" data-queue-summary role="group" aria-label="Queue Line summary">
    <div class="flex flex-col justify-center rounded-lg border-2 border-gray-800 bg-gray-900 text-white px-4 py-2 wall4k:px-6 wall4k:py-3">
        <span class="{{ $ui['badge'] }} font-medium uppercase tracking-wide text-gray-300">Equipment Cards</span>
        <span class="{{ $ui['sectionHeader'] }} font-bold leading-tight" data-summary-count="cards">{{ $summary['cards'] }}</span>
    </div>

    @foreach ($tiles as [$key, $label, $icon, $tone])
        @php $count = $summary[$key]; @endphp
        <div class="flex flex-col justify-center rounded-lg border-2 px-4 py-2 wall4k:px-6 wall4k:py-3 {{ $count > 0 ? $tone : 'bg-gray-50 text-gray-400 border-gray-200' }}"
            data-summary-count="{{ $key }}">
            <span class="flex items-center gap-1 {{ $ui['badge'] }} font-medium uppercase tracking-wide">
                @if ($icon === 'bolt')
                    <x-heroicon-s-bolt class="w-4 h-4 wall4k:w-6 wall4k:h-6" aria-hidden="true" />
                @elseif ($icon === 'check')
                    <x-heroicon-s-check-circle class="w-4 h-4 wall4k:w-6 wall4k:h-6" aria-hidden="true" />
                @elseif ($icon === 'fire')
                    <x-heroicon-s-fire class="w-4 h-4 wall4k:w-6 wall4k:h-6" aria-hidden="true" />
                @elseif ($icon === 'wrench')
                    <x-heroicon-s-wrench-screwdriver class="w-4 h-4 wall4k:w-6 wall4k:h-6" aria-hidden="true" />
                @elseif ($icon === 'exclamation-triangle')
                    <x-heroicon-s-exclamation-triangle class="w-4 h-4 wall4k:w-6 wall4k:h-6" aria-hidden="true" />
                @endif
                {{ $label }}
            </span>
            <span class="{{ $ui['sectionHeader'] }} font-bold leading-tight">{{ $count }}</span>
        </div>
    @endforeach
</div>
