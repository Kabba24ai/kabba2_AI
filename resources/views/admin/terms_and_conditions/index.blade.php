@extends('admin.layouts.app')

@section('title', 'Terms and Condition')

@push('css')
@endpush

@section('content')

    @include('flash::message')

    {{-- Header --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h3 class="text-xl font-semibold text-gray-800 dark:text-white/90">Terms</h3>
        <a href="{{ route('admin.terms-and-conditions.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600 focus:outline-none focus:ring-2 focus:ring-brand-400 dark:focus:ring-brand-500">
            + Create Terms
        </a>
    </div>

    {{-- Search and Filters --}}
    <form method="GET" action="{{ route('admin.terms-and-conditions.index') }}" class="mb-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            {{-- Search Input --}}
            <div class="flex-1 relative">
                <input type="text" name="search" placeholder="Search terms..." value="{{ request('search') }}"
                    class="w-full rounded-md border border-gray-300 bg-white px-4 py-2 pr-10 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600" />
                <div class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                        stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8" />
                        <line x1="21" y1="21" x2="16.65" y2="16.65" />
                    </svg>
                </div>
            </div>

            {{-- Type Dropdown --}}
            <select name="is_global"
                class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 dark:bg-gray-800 dark:text-white dark:border-gray-600">
                <option value="">All Terms</option>
                <option value="Yes" @selected(request('is_global') === 'Yes')>Global</option>
                <option value="No" @selected(request('is_global') === 'No')>Product Specific</option>
            </select>
        </div>
    </form>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-lg shadow-sm ring-1 ring-gray-200 dark:ring-gray-700">
        <table class="table-fixed w-full divide-y divide-gray-200 dark:divide-gray-700 text-sm">
            {{-- Table Header --}}
            <thead class="bg-gray-50 dark:bg-gray-900">
                <tr>
                    <th class="w-2/5 px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Title</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Type</th>

                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Created</th>
                    <th class="px-6 py-4 text-left font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Status</th>
                    <th
                        class="px-6 py-4 text-right font-semibold text-gray-600 dark:text-gray-300 uppercase tracking-wider">
                        Actions</th>
                </tr>
            </thead>

            {{-- Table Body --}}
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800 bg-white dark:bg-gray-950">
                @forelse($terms as $t)
                    <tr class="{{($t->is_global=='Yes' ? 'bg-gray-200' : '')}} hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-gray-800 dark:text-gray-100">{{ $t->title }}</div>
                            <div class="text-gray-500 text-xs truncate">{!! Str::limit(strip_tags($t->content), 50) !!}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $t->is_global === 'Yes'
                                    ? 'bg-sky-100 text-sky-700 dark:bg-sky-500/10 dark:text-sky-400'
                                    : 'bg-yellow-100 text-yellow-700 dark:bg-yellow-500/10 dark:text-yellow-400' }}">
                                {{ ($t->is_global=='Yes' ? 'Global' : 'Product') }}
                            </span>
                        </td>

                        <td class="px-6 py-4 text-gray-600 dark:text-gray-400">{{ $t->created_at->format('Y-m-d') }}</td>
                        <td class="px-6 py-4">
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $t->status === 'Published'
                                    ? 'bg-green-100 text-green-700 dark:bg-green-500/10 dark:text-green-400'
                                    : 'bg-red-100 text-red-700 dark:bg-red-500/10 dark:text-red-400' }}">
                                {{ $t->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                        	<div class="flex float-right gap-x-3">
                            		<a href="{{ route('admin.terms-and-conditions.edit', $t->unique_id   ) }}"
                                        class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                        title="Edit">
                                        <x-heroicon-o-pencil class="w-5 h-5" />
                                    </a>
                                    @if($t->is_global=='No')
                                    <form action="{{ route('admin.terms-and-conditions.delete', $t->unique_id    ) }}"
                                        method="POST"
                                        onsubmit="return confirm('Are you sure you want to delete this option?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                            title="Delete">
                                            <x-heroicon-o-trash class="w-5 h-5" />
                                        </button>
                                    </form>
                                    @endif
                            </div>

                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-6 text-center text-gray-500 dark:text-gray-400">
                            No terms found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    <div class="mt-6">
        {{ $terms->links() }}
    </div>
@endsection

@push('js')
@endpush
