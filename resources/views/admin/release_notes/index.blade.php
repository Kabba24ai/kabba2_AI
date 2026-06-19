@extends('admin.layouts.app')

@section('title', 'Release Notes')

@push('css')
<style>
    /* Prose styles for rendered markdown */
    .release-prose h1 { font-size: 1.6rem; font-weight: 700; color: #111827; margin: 0 0 1rem; line-height: 1.25; }
    .release-prose h2 { font-size: 1.15rem; font-weight: 700; color: #111827; margin: 2rem 0 0.6rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb; }
    .release-prose h2:first-of-type { margin-top: 1rem; border-top: none; padding-top: 0; }
    .release-prose h3 { font-size: 1rem; font-weight: 600; color: #374151; margin: 1.25rem 0 0.4rem; }
    .release-prose p  { color: #4b5563; line-height: 1.7; margin: 0.5rem 0 1rem; }
    .release-prose ul { list-style: none; margin: 0 0 1rem; padding: 0; }
    .release-prose ul li { position: relative; padding: 0.35rem 0 0.35rem 1.25rem; color: #374151; font-size: 0.9rem; line-height: 1.55; border-bottom: 1px solid #f3f4f6; }
    .release-prose ul li:last-child { border-bottom: none; }
    .release-prose ul li::before { content: ''; position: absolute; left: 0; top: 0.7rem; width: 6px; height: 6px; border-radius: 50%; background: #6366f1; }
    .release-prose strong { font-weight: 600; color: #111827; }
    .release-prose code { font-size: 0.8rem; background: #f3f4f6; padding: 1px 5px; border-radius: 3px; font-family: monospace; }

    /* Section header colour coding */
    .release-prose h2:nth-of-type(1) { color: #4f46e5; }
    .release-prose h2:nth-of-type(1) ~ ul li::before { background: #4f46e5; }
    .release-prose h2:nth-of-type(2) { color: #0891b2; }
    .release-prose h2:nth-of-type(2) ~ ul li::before { background: #0891b2; }
    .release-prose h2:nth-of-type(3) { color: #16a34a; }
    .release-prose h2:nth-of-type(3) ~ ul li::before { background: #16a34a; }
    .release-prose h2:nth-of-type(4) { color: #d97706; }
    .release-prose h2:nth-of-type(4) ~ ul li::before { background: #d97706; }
    .release-prose h2:nth-of-type(5) { color: #dc2626; }
    .release-prose h2:nth-of-type(5) ~ ul li::before { background: #dc2626; }
    .release-prose h2:nth-of-type(6) { color: #7c3aed; }
    .release-prose h2:nth-of-type(6) ~ ul li::before { background: #7c3aed; }

    /* Sidebar active state */
    .release-nav-item.active { background: #eef2ff; border-left: 3px solid #4f46e5; }
    .release-nav-item.active .release-nav-label { color: #4f46e5; font-weight: 600; }
    .release-nav-item { border-left: 3px solid transparent; }

    /* Email summary card */
    .release-prose h2:last-of-type + p {
        background: #fefce8;
        border: 1px solid #fde68a;
        border-radius: 8px;
        padding: 1rem 1.25rem;
        color: #92400e;
        font-size: 0.9rem;
        line-height: 1.65;
    }
</style>
@endpush

@section('content')
<div class="max-w-7xl mx-auto px-4 py-6">

    {{-- Page header --}}
    <div class="mb-6 flex items-center gap-3">
        <div class="flex items-center justify-center w-10 h-10 rounded-lg bg-indigo-100">
            <svg class="w-5 h-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
            </svg>
        </div>
        <div>
            <h1 class="text-xl font-bold text-gray-900">What's New</h1>
            <p class="text-sm text-gray-500">Release notes &amp; improvements</p>
        </div>
    </div>

    <div class="flex gap-6 items-start">

        {{-- Left sidebar: list of docs --}}
        <aside class="w-64 flex-shrink-0 bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-100 bg-gray-50">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">All Releases</p>
            </div>

            @if($allFiles->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-gray-400">
                    No release notes found.<br>
                    Run <code class="text-xs bg-gray-100 px-1 rounded">docs:new-features</code> to generate one.
                </div>
            @else
                <nav class="py-1">
                    @foreach($allFiles as $file)
                        <a href="{{ route('admin.release-notes.show', $file['slug']) }}"
                           class="release-nav-item flex flex-col px-4 py-3 hover:bg-gray-50 transition-colors
                                  {{ $slug === $file['slug'] ? 'active' : '' }}">
                            <span class="release-nav-label text-sm text-gray-700 leading-tight truncate">
                                {{ $file['label'] }}
                            </span>
                            <span class="text-xs text-gray-400 mt-0.5">{{ $file['date'] }}</span>
                        </a>
                    @endforeach
                </nav>
            @endif

        </aside>

        {{-- Right content area --}}
        <main class="flex-1 min-w-0 bg-white border border-gray-200 rounded-xl shadow-sm">

            @if($notFound)
                <div class="flex flex-col items-center justify-center py-24 text-center px-8">
                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414A1 1 0 0119 9.414V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-700 mb-1">No document found</h2>
                    <p class="text-sm text-gray-400">This release note does not exist or has been removed.</p>
                </div>

            @elseif(!$content)
                <div class="flex flex-col items-center justify-center py-24 text-center px-8">
                    <svg class="w-12 h-12 text-gray-300 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <h2 class="text-lg font-semibold text-gray-700 mb-1">No release notes yet</h2>
                    <p class="text-sm text-gray-400 mb-4">Generate your first one using the Artisan command shown in the sidebar.</p>
                </div>

            @else
                {{-- Top bar with meta --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 bg-gray-50 rounded-t-xl">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-medium">
                            <span class="w-1.5 h-1.5 rounded-full bg-indigo-500 inline-block"></span>
                            Latest
                        </span>
                        <span class="text-sm text-gray-500">{{ $allFiles->where('slug', $slug)->first()['label'] ?? $slug }}</span>
                    </div>
                    <span class="text-xs text-gray-400">{{ $allFiles->where('slug', $slug)->first()['date'] ?? '' }}</span>
                </div>

                {{-- Rendered markdown --}}
                <div class="px-8 py-6 release-prose">
                    {!! $content !!}
                </div>
            @endif
        </main>

    </div>
</div>
@endsection
