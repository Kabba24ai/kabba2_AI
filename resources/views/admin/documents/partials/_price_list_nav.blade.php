{{-- Price List module header + internal sub-navigation (one sidebar entry, three sections) --}}
@php
    $plTabs = [
        [
            'label'  => 'Generate',
            'route'  => route('admin.documents.price-list.form'),
            'active' => Route::is('admin.documents.price-list.form'),
            'icon'   => 'heroicon-o-printer',
        ],
        [
            'label'  => 'Industry Presets',
            'route'  => route('admin.documents.presets.index'),
            'active' => Route::is('admin.documents.presets.*'),
            'icon'   => 'heroicon-o-squares-2x2',
        ],
        [
            'label'  => 'Document Text',
            'route'  => route('admin.documents.price-list.text'),
            'active' => Route::is('admin.documents.price-list.text'),
            'icon'   => 'heroicon-o-pencil-square',
        ],
    ];
@endphp

<div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 mb-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-semibold text-gray-900 flex items-center gap-2">
                <x-heroicon-o-document-text class="w-6 h-6 text-blue-600" />
                Customer Price List
            </h1>
            <p class="text-sm text-gray-500 mt-1.5 max-w-2xl">
                {{ $plSubtitle ?? 'Generate and manage the printable take-home price list. Informational only — website pricing always governs.' }}
            </p>
        </div>
        @isset($plHeaderAction)
            {{ $plHeaderAction }}
        @endisset
    </div>

    <nav class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100">
        @foreach ($plTabs as $tab)
            <a href="{{ $tab['route'] }}"
                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-lg text-sm font-medium transition
                {{ $tab['active']
                    ? 'bg-blue-600 text-white'
                    : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100 border border-gray-200' }}">
                <x-dynamic-component :component="$tab['icon']" class="w-4 h-4" />
                {{ $tab['label'] }}
            </a>
        @endforeach
    </nav>
</div>
