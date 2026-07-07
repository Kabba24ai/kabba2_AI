{{-- Price List module header + segmented tab navigation (one sidebar entry, three sections) --}}
@php
    $plTabs = [
        [
            'number' => 1,
            'label'  => 'Generate',
            'route'  => route('admin.documents.price-list.form'),
            'active' => Route::is('admin.documents.price-list.form'),
        ],
        [
            'number' => 2,
            'label'  => 'Industry Presets',
            'route'  => route('admin.documents.presets.index'),
            'active' => Route::is('admin.documents.presets.*'),
        ],
        [
            'number' => 3,
            'label'  => 'Document Text',
            'route'  => route('admin.documents.price-list.text'),
            'active' => Route::is('admin.documents.price-list.text'),
        ],
    ];
@endphp

<div class="bg-white rounded-xl border border-gray-200 shadow-sm mb-6 overflow-hidden">
    <div class="p-5 pb-4">
        <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2.5">
            <span class="w-9 h-9 rounded-lg bg-blue-50 grid place-items-center shrink-0">
                <x-heroicon-o-document-text class="w-5 h-5 text-blue-600" />
            </span>
            Customer Price List
        </h1>
        <p class="text-sm text-gray-500 mt-2 max-w-3xl">
            {{ $plSubtitle ?? 'Generate a printable take-home price list for walk-in customers. Informational only — website pricing always governs. Select the categories to include; products print in their website display order with live pricing.' }}
        </p>
    </div>

    {{-- Segmented module tabs --}}
    <nav class="grid grid-cols-3 border-t border-gray-100">
        @foreach ($plTabs as $tab)
            <a href="{{ $tab['route'] }}"
                class="flex items-center justify-center gap-2.5 px-3 py-3.5 text-sm font-semibold border-b-[3px] transition
                {{ $tab['active']
                    ? 'bg-blue-50/70 text-blue-700 border-blue-600'
                    : 'bg-gray-50/60 text-gray-500 border-transparent hover:text-gray-800 hover:bg-gray-50' }}
                {{ !$loop->last ? 'border-r border-r-gray-100' : '' }}">
                <span class="w-6 h-6 rounded-full grid place-items-center text-xs font-bold shrink-0
                    {{ $tab['active'] ? 'bg-blue-600 text-white' : 'bg-gray-200 text-gray-600' }}">
                    {{ $tab['number'] }}
                </span>
                <span class="truncate">{{ $tab['label'] }}</span>
            </a>
        @endforeach
    </nav>
</div>
