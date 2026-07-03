@extends('admin.layouts.app')

@section('title', 'Revision #' . $revision->revision_number . ' — ' . $page->title)

@section('content')

<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

    {{-- Header --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-5 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.website-management.pages.revisions.index', $page->unique_id) }}"
               class="text-gray-400 hover:text-gray-600 transition-colors">
                <x-heroicon-o-arrow-left class="w-5 h-5"/>
            </a>
            <div>
                <h1 class="text-lg font-semibold text-gray-900">
                    Revision #{{ $revision->revision_number }}
                    @if($revision->is_published_snapshot)
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700 ml-2">Published snapshot</span>
                    @endif
                </h1>
                <p class="text-sm text-gray-400">
                    {{ $page->title }}
                    &mdash; {{ $revision->created_at->format('M j, Y H:i') }}
                    @if($revision->author) by <strong>{{ $revision->author->name }}</strong> @endif
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            @unless($revision->trashed())
            <button onclick="restoreRevision()"
                    class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-md text-sm font-medium transition-colors">
                <x-heroicon-o-arrow-path class="w-4 h-4"/>
                Restore This Revision
            </button>
            @endunless
        </div>
    </div>

    @if($revision->change_summary)
    <div class="bg-blue-50 border border-blue-100 rounded-lg px-4 py-3 text-sm text-blue-700 mb-5 flex items-start gap-2">
        <x-heroicon-o-chat-bubble-left-ellipsis class="w-4 h-4 mt-0.5 shrink-0"/>
        {{ $revision->change_summary }}
    </div>
    @endif

    @php $snap = $revision->snapshot; @endphp

    {{-- Page Settings --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-4">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center gap-2">
            <x-heroicon-o-document-text class="w-4 h-4 text-gray-400"/>
            <h2 class="text-sm font-semibold text-gray-700">Page Settings</h2>
        </div>
        <dl class="divide-y divide-gray-50 px-5">
            @foreach([
                'title'            => 'Title',
                'slug'             => 'Slug',
                'meta_title'       => 'Meta Title',
                'meta_description' => 'Meta Description',
                'meta_keywords'    => 'Meta Keywords',
                'og_title'         => 'OG Title',
                'og_description'   => 'OG Description',
                'canonical_url'    => 'Canonical URL',
                'publish_status'   => 'Publish Status',
            ] as $field => $label)
            @if(!empty($snap['page'][$field]))
            <div class="py-2.5 grid grid-cols-3 gap-2">
                <dt class="text-xs font-medium text-gray-500">{{ $label }}</dt>
                <dd class="col-span-2 text-sm text-gray-800 break-words">{{ $snap['page'][$field] }}</dd>
            </div>
            @endif
            @endforeach
        </dl>
    </div>

    {{-- Sections --}}
    @forelse($snap['sections'] ?? [] as $i => $section)
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-3">
        <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="text-xs font-mono bg-blue-50 text-blue-600 px-2 py-0.5 rounded">{{ $section['section_type'] ?? $section['section_key'] }}</span>
                <h3 class="text-sm font-medium text-gray-700">{{ $section['section_name'] ?? $section['section_key'] }}</h3>
            </div>
            <span class="text-xs {{ ($section['status'] ?? 'Active') === 'Active' ? 'text-green-600' : 'text-gray-400' }}">
                {{ $section['status'] ?? 'Active' }}
            </span>
        </div>
        <dl class="divide-y divide-gray-50 px-5">
            @foreach(['title' => 'Title', 'subtitle' => 'Subtitle', 'button_text' => 'Button Text', 'button_url' => 'Button URL'] as $f => $l)
                @if(!empty($section[$f]))<div class="py-2 grid grid-cols-3 gap-2"><dt class="text-xs text-gray-500">{{ $l }}</dt><dd class="col-span-2 text-sm text-gray-800">{{ $section[$f] }}</dd></div>@endif
            @endforeach
        </dl>

        @if(!empty($section['items']))
        <div class="px-5 pb-3">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide pt-3 pb-1.5">Items ({{ count($section['items']) }})</p>
            <div class="space-y-2">
                @foreach($section['items'] as $item)
                <div class="bg-gray-50 rounded-lg px-4 py-2.5 border border-gray-100">
                    <p class="text-sm font-medium text-gray-800">{{ $item['title'] ?? '—' }}</p>
                    @if(!empty($item['subtitle']))<p class="text-xs text-gray-500">{{ $item['subtitle'] }}</p>@endif
                    @if(!empty($item['description']))<p class="text-xs text-gray-400 mt-0.5 line-clamp-2">{{ $item['description'] }}</p>@endif
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
    @empty
    <div class="bg-white rounded-xl border border-gray-100 px-5 py-8 text-center text-sm text-gray-400">
        No sections were captured in this revision.
    </div>
    @endforelse

</div>

<script>
function restoreRevision() {
    if (!confirm('Restore page to revision #{{ $revision->revision_number }}?\n\nA new revision will be created from this state. No history will be lost.')) return;
    fetch('{{ route("admin.website-management.pages.revisions.restore", [$page->unique_id, $revision->unique_id]) }}', {
        method:  'POST',
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    }).then(r => r.json()).then(d => {
        if (d.success) {
            alert(d.message);
            location.href = d.redirect ?? '{{ route("admin.website-management.pages.revisions.index", $page->unique_id) }}';
        } else {
            alert(d.message ?? 'Restore failed.');
        }
    });
}
</script>

@endsection
