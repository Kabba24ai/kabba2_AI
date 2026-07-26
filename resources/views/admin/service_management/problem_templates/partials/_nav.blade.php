{{-- Shared Problem Library navigation — included on every sub-page so no screen
     is a dead end. Pass ['active' => 'library'|'templates'|'equipment']. --}}
@php
    $plActive = $active ?? '';
    $plTabs = [
        'library'   => ['admin.service-management.problem-templates.index',    'Problem Library'],
        'templates' => ['admin.service-management.problem-templates.templates', 'Templates'],
        'equipment' => ['admin.service-management.problem-templates.equipment', 'Equipment Attachment'],
    ];
@endphp
<div class="flex flex-wrap items-center justify-between gap-3">
    <nav class="inline-flex flex-wrap items-center gap-1 rounded-lg border border-gray-200 bg-gray-50 p-1">
        @foreach ($plTabs as $plKey => $plTab)
            <a href="{{ route($plTab[0]) }}"
               class="px-3 py-1.5 rounded-md text-sm font-medium transition {{ $plActive === $plKey ? 'bg-white text-blue-700 border border-gray-200 shadow-sm' : 'text-gray-600 hover:text-gray-900 hover:bg-white' }}">
                {{ $plTab[1] }}
            </a>
        @endforeach
    </nav>
    <a href="{{ route('admin.service-management.problem-templates.create') }}"
       class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition">
        <x-heroicon-o-plus class="w-4 h-4" /> New Template
    </a>
</div>
