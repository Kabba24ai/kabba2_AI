@extends('admin.layouts.app')

@section('title', 'Parts List Templates')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="p-6">
        {{-- Header --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <div class="flex items-center space-x-3 mb-2">
                <svg class="h-8 w-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h1 class="text-3xl font-bold text-gray-900">Parts List Templates</h1>
            </div>
            <p class="text-gray-600">Create and manage reusable parts lists for different equipment categories</p>
        </div>

        {{-- Filter Controls --}}
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-100 mb-6">
            <form method="GET" action="{{ route('admin.templates.index') }}" class="flex items-center justify-between space-x-4">
                <div class="flex items-center space-x-4 flex-1">
                    {{-- Search --}}
                    <div class="relative max-w-sm">
                        <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search templates..." 
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-colors">
                    </div>

                    {{-- Category Filter --}}
                    <select name="category" class="px-3 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-purple-500 bg-white min-w-[160px]">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                        @endforeach
                    </select>

                    <button type="submit" class="px-4 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                        Filter
                    </button>

                    @if(request()->hasAny(['search', 'category']))
                        <a href="{{ route('admin.templates.index') }}" class="px-4 py-3 text-gray-600 hover:text-gray-800 transition-colors">
                            Clear
                        </a>
                    @endif
                </div>

                {{-- Create Template Button --}}
                <a href="{{ route('admin.templates.create') }}" class="flex items-center space-x-2 px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors flex-shrink-0">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    <span>Create Template</span>
                </a>
            </form>
        </div>

        {{-- Templates Table --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            @if($templates->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Template Name</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Category</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Description</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Parts Count</th>
                                <th class="text-left py-4 px-6 font-semibold text-gray-700">Created By</th>
                                <th class="text-left py-3 px-3 font-semibold text-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($templates as $template)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="py-4 px-6">
                                        <div>
                                            <span class="text-sm font-medium text-gray-900">{{ $template->name }}</span>
                                            <p class="text-xs text-gray-500 mt-1">Modified: {{ $template->updated_at->format('Y-m-d') }}</p>
                                        </div>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            {{ $template->category }}
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm text-gray-900">{{ $template->description }}</span>
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ $template->parts_count }} parts
                                        </span>
                                    </td>
                                    <td class="py-4 px-6">
                                        <span class="text-sm text-gray-900">{{ $template->created_by }}</span>
                                    </td>
                                    <td class="py-3 px-3">
                                        <div class="flex items-center space-x-1">
                                            <a href="{{ route('admin.templates.edit', $template->unique_id) }}" 
                                               class="p-1 text-yellow-600 hover:text-yellow-800 hover:bg-yellow-50 rounded-lg transition-colors" 
                                               title="Edit Template">
                                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                            </a>
                                            <form action="{{ route('admin.templates.delete', $template->unique_id) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        onclick="return confirm('Are you sure you want to delete {{ $template->name }}?')"
                                                        class="p-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors" 
                                                        title="Delete Template">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center py-12">
                    <svg class="h-12 w-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No templates found</h3>
                    <p class="text-gray-600 mb-4">Try adjusting your search criteria or create a new template.</p>
                    <a href="{{ route('admin.templates.create') }}" class="inline-flex items-center space-x-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg font-medium transition-colors">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        <span>Create Template</span>
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
