@extends('admin.layouts.app')

@section('title', 'Website Pages')

@section('content')

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    {{-- Page Header --}}
    <div class="bg-white rounded-md p-5 shadow-sm border border-gray-100 mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center space-x-3 mb-2">
                <x-heroicon-o-document-text class="w-8 h-8 text-blue-600"/>
                <h1 class="text-2xl font-semibold text-gray-900">Website Pages</h1>
            </div>
            <p class="text-gray-600">Create and manage content pages for your website.</p>
        </div>
        <a href="{{ route('admin.website-management.pages.create') }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2.5 rounded-md text-sm font-medium shadow-sm transition-colors whitespace-nowrap">
            <x-heroicon-o-plus class="w-4 h-4"/>
            New Page
        </a>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 rounded-md px-4 py-3 text-sm mb-6 flex items-center gap-2">
        <x-heroicon-o-check-circle class="w-4 h-4 shrink-0"/>
        {{ session('success') }}
    </div>
    @endif

    {{-- Pages Table --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        @if($pages->isEmpty())
            <div class="px-6 py-16 text-center">
                <x-heroicon-o-document-text class="w-12 h-12 text-gray-300 mx-auto mb-3"/>
                <p class="text-gray-500 text-sm">No pages yet.</p>
                <a href="{{ route('admin.website-management.pages.create') }}"
                   class="mt-3 inline-flex items-center gap-1.5 text-sm text-blue-600 hover:underline">
                    <x-heroicon-o-plus class="w-4 h-4"/> Create your first page
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Title</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Slug</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Page Key</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Updated</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($pages as $page)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-5 py-3.5 font-medium text-gray-900">{{ $page->title }}</td>
                            <td class="px-5 py-3.5 text-gray-500 font-mono text-xs">{{ $page->slug }}</td>
                            <td class="px-5 py-3.5">
                                @if($page->page_key)
                                    <code class="text-xs bg-gray-100 text-gray-600 px-1.5 py-0.5 rounded">{{ $page->page_key }}</code>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $page->status === 'Active' ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }}">
                                    {{ $page->status }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-gray-400 text-xs">{{ $page->updated_at?->diffForHumans() }}</td>
                            <td class="px-5 py-3.5 text-right">
                                <a href="{{ route('admin.website-management.pages.edit', $page->unique_id) }}"
                                   class="inline-flex items-center gap-1.5 bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors">
                                    <x-heroicon-o-pencil-square class="w-3.5 h-3.5"/>
                                    Edit Builder
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>

@endsection
