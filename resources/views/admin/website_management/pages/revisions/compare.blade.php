@extends('admin.layouts.app')

@section('title', 'Compare Revisions — ' . $page->title)

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.website-management.pages.revisions.index', $page->unique_id) }}"
               class="text-gray-400 hover:text-gray-600">
                <x-heroicon-o-arrow-left class="w-5 h-5"/>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">Compare Revisions</h1>
                <p class="text-sm text-gray-400">{{ $page->title }}</p>
            </div>
        </div>

        {{-- Revision pickers --}}
        <form method="GET" class="flex flex-wrap items-center gap-2 text-sm">
            <label class="text-gray-500 text-xs font-medium">Base</label>
            <select name="a" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                @foreach($revisions as $rev)
                    <option value="{{ $rev->unique_id }}"
                            {{ $base->unique_id === $rev->unique_id ? 'selected' : '' }}>
                        #{{ $rev->revision_number }} — {{ $rev->created_at->format('M j H:i') }}{{ $rev->change_summary ? ' — ' . Str::limit($rev->change_summary, 25) : '' }}
                    </option>
                @endforeach
            </select>
            <span class="text-gray-400">vs</span>
            <label class="text-gray-500 text-xs font-medium">Compare</label>
            <select name="b" onchange="this.form.submit()" class="text-sm border border-gray-200 rounded-md px-2 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-400 bg-white">
                @foreach($revisions as $rev)
                    <option value="{{ $rev->unique_id }}"
                            {{ $compare->unique_id === $rev->unique_id ? 'selected' : '' }}>
                        #{{ $rev->revision_number }} — {{ $rev->created_at->format('M j H:i') }}{{ $rev->change_summary ? ' — ' . Str::limit($rev->change_summary, 25) : '' }}
                    </option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Summary banner --}}
    @if(!$diff['has_changes'])
    <div class="bg-green-50 border border-green-100 rounded-lg px-4 py-3 text-sm text-green-700 mb-5 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        No differences found between these two revisions.
    </div>
    @else
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
        @php $s = $diff['summary']; @endphp
        <div class="bg-white rounded-lg border border-gray-100 p-3 text-center shadow-sm">
            <p class="text-2xl font-bold {{ $s['page_field_changes'] > 0 ? 'text-amber-600' : 'text-gray-300' }}">{{ $s['page_field_changes'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Page field changes</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3 text-center shadow-sm">
            <p class="text-2xl font-bold {{ $s['sections_added'] > 0 ? 'text-green-600' : 'text-gray-300' }}">{{ $s['sections_added'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Sections added</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3 text-center shadow-sm">
            <p class="text-2xl font-bold {{ $s['sections_removed'] > 0 ? 'text-red-600' : 'text-gray-300' }}">{{ $s['sections_removed'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Sections removed</p>
        </div>
        <div class="bg-white rounded-lg border border-gray-100 p-3 text-center shadow-sm">
            <p class="text-2xl font-bold {{ $s['sections_changed'] > 0 ? 'text-blue-600' : 'text-gray-300' }}">{{ $s['sections_changed'] }}</p>
            <p class="text-xs text-gray-500 mt-0.5">Sections changed</p>
        </div>
    </div>
    @endif

    {{-- Side-by-side revision headers --}}
    <div class="grid grid-cols-2 gap-4 mb-3">
        <div class="bg-white rounded-xl border border-gray-100 shadow-sm px-4 py-3">
            <p class="text-xs text-gray-400 font-medium uppercase tracking-wide">Base (older)</p>
            <p class="text-sm font-semibold text-gray-800 mt-0.5">Revision #{{ $base->revision_number }}</p>
            <p class="text-xs text-gray-400">{{ $base->created_at->format('M j, Y H:i') }} &bull; {{ $base->author?->name ?? 'System' }}</p>
            @if($base->change_summary)<p class="text-xs text-gray-500 mt-1 italic">{{ $base->change_summary }}</p>@endif
        </div>
        <div class="bg-white rounded-xl border border-blue-200 shadow-sm px-4 py-3">
            <p class="text-xs text-blue-500 font-medium uppercase tracking-wide">Compare (newer)</p>
            <p class="text-sm font-semibold text-gray-800 mt-0.5">Revision #{{ $compare->revision_number }}</p>
            <p class="text-xs text-gray-400">{{ $compare->created_at->format('M j, Y H:i') }} &bull; {{ $compare->author?->name ?? 'System' }}</p>
            @if($compare->change_summary)<p class="text-xs text-gray-500 mt-1 italic">{{ $compare->change_summary }}</p>@endif
        </div>
    </div>

    {{-- ── Page Settings Diff ────────────────────────────────────────────── --}}
    @if(!empty($diff['page_settings']))
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <x-heroicon-o-document-text class="w-4 h-4 text-amber-500"/>
            <h2 class="text-sm font-semibold text-gray-700">Page Settings Changes</h2>
            <span class="ml-auto text-xs text-amber-600 font-medium">{{ count($diff['page_settings']) }} change{{ count($diff['page_settings']) !== 1 ? 's' : '' }}</span>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($diff['page_settings'] as $change)
            <div class="grid grid-cols-3 gap-0 text-sm">
                <div class="px-5 py-2.5 bg-gray-50 border-r border-gray-100">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wide">{{ str_replace('_', ' ', $change['field']) }}</span>
                </div>
                <div class="px-4 py-2.5 bg-red-50 border-r border-gray-100">
                    <span class="text-red-700 text-xs line-through break-words">{{ $change['from'] ?? '—' }}</span>
                </div>
                <div class="px-4 py-2.5 bg-green-50">
                    <span class="text-green-800 text-xs break-words">{{ $change['to'] ?? '—' }}</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- ── Sections Added ────────────────────────────────────────────────── --}}
    @if(!empty($diff['sections']['added']))
    <div class="bg-white rounded-xl shadow-sm border border-green-100 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-green-100 bg-green-50/50 flex items-center gap-2">
            <x-heroicon-o-plus-circle class="w-4 h-4 text-green-600"/>
            <h2 class="text-sm font-semibold text-green-700">Sections Added ({{ count($diff['sections']['added']) }})</h2>
        </div>
        @foreach($diff['sections']['added'] as $section)
        <div class="px-5 py-3 border-b border-green-50 last:border-0">
            <span class="text-xs font-mono bg-green-100 text-green-700 px-2 py-0.5 rounded mr-2">{{ $section['section_type'] ?? $section['section_key'] }}</span>
            <span class="text-sm text-green-800">{{ $section['section_name'] ?? $section['section_key'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Sections Removed ──────────────────────────────────────────────── --}}
    @if(!empty($diff['sections']['removed']))
    <div class="bg-white rounded-xl shadow-sm border border-red-100 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-red-100 bg-red-50/50 flex items-center gap-2">
            <x-heroicon-o-minus-circle class="w-4 h-4 text-red-500"/>
            <h2 class="text-sm font-semibold text-red-600">Sections Removed ({{ count($diff['sections']['removed']) }})</h2>
        </div>
        @foreach($diff['sections']['removed'] as $section)
        <div class="px-5 py-3 border-b border-red-50 last:border-0 bg-red-50/30">
            <span class="text-xs font-mono bg-red-100 text-red-600 px-2 py-0.5 rounded mr-2">{{ $section['section_type'] ?? $section['section_key'] }}</span>
            <span class="text-sm text-red-700 line-through">{{ $section['section_name'] ?? $section['section_key'] }}</span>
        </div>
        @endforeach
    </div>
    @endif

    {{-- ── Sections Changed ──────────────────────────────────────────────── --}}
    @foreach($diff['sections']['changed'] ?? [] as $section)
    <div class="bg-white rounded-xl shadow-sm border border-amber-100 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-amber-100 bg-amber-50/40 flex items-center gap-2">
            <x-heroicon-o-pencil-square class="w-4 h-4 text-amber-600"/>
            <h2 class="text-sm font-semibold text-amber-700">{{ $section['name'] }}</h2>
        </div>

        {{-- Field changes --}}
        @if(!empty($section['field_changes']))
        <div class="px-5 pt-3 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Section Fields</p>
        </div>
        <div class="divide-y divide-gray-50">
            @foreach($section['field_changes'] as $change)
            <div class="grid grid-cols-3 gap-0 text-sm">
                <div class="px-5 py-2 bg-gray-50 border-r border-gray-100">
                    <span class="text-xs font-medium text-gray-500">{{ str_replace('_', ' ', $change['field']) }}</span>
                </div>
                <div class="px-4 py-2 bg-red-50 border-r border-gray-100 text-xs text-red-700 break-words line-through">
                    {{ is_array($change['from']) ? json_encode($change['from']) : ($change['from'] ?? '—') }}
                </div>
                <div class="px-4 py-2 bg-green-50 text-xs text-green-800 break-words">
                    {{ is_array($change['to']) ? json_encode($change['to']) : ($change['to'] ?? '—') }}
                </div>
            </div>
            @endforeach
        </div>
        @endif

        {{-- Item changes --}}
        @if(!empty($section['item_changes']['added']) || !empty($section['item_changes']['removed']) || !empty($section['item_changes']['changed']))
        <div class="px-5 pt-3 pb-1">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Item Changes</p>
        </div>
        @foreach($section['item_changes']['added'] ?? [] as $item)
        <div class="px-5 py-2 bg-green-50/50 text-xs text-green-700 flex items-center gap-2">
            <x-heroicon-o-plus class="w-3.5 h-3.5 shrink-0"/> Added item: {{ $item['title'] ?? '—' }}
        </div>
        @endforeach
        @foreach($section['item_changes']['removed'] ?? [] as $item)
        <div class="px-5 py-2 bg-red-50/50 text-xs text-red-600 flex items-center gap-2 line-through">
            <x-heroicon-o-minus class="w-3.5 h-3.5 shrink-0 no-underline"/> Removed item: {{ $item['title'] ?? '—' }}
        </div>
        @endforeach
        @foreach($section['item_changes']['changed'] ?? [] as $itemChange)
        <div class="px-5 py-2 bg-amber-50/40 text-xs text-amber-700 border-t border-amber-50">
            <p class="font-medium mb-1">Modified item: {{ $itemChange['title'] ?? $itemChange['unique_id'] }}</p>
            @foreach($itemChange['changes'] as $c)
            <span class="inline-flex items-center gap-1 mr-3">
                <span class="text-gray-500">{{ str_replace('_', ' ', $c['field']) }}:</span>
                <span class="line-through text-red-600">{{ is_array($c['from']) ? '…' : ($c['from'] ?? '—') }}</span>
                <span class="text-green-700">→ {{ is_array($c['to']) ? '…' : ($c['to'] ?? '—') }}</span>
            </span>
            @endforeach
        </div>
        @endforeach
        @endif
    </div>
    @endforeach

    @if(!$diff['has_changes'])
    <div class="py-6 text-center text-gray-400 text-sm">No further changes detected.</div>
    @endif

</div>

@endsection
