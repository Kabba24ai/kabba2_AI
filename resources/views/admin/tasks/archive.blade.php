@extends('admin.layouts.app')

@section('title', 'Task Archive')

@section('content')

@include('flash::message')

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <div>
        <div class="flex items-center gap-3 mb-1">
            <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
                <x-heroicon-o-arrow-left class="w-5 h-5" />
            </a>
            <h3 class="text-xl font-semibold text-gray-800">Task Archive</h3>
        </div>
        <p class="text-sm text-gray-500 ml-8">Historical record of all completed tasks.</p>
    </div>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.tasks.archive') }}"
      class="mb-5 bg-white rounded-lg border border-gray-200 shadow-sm p-4">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 items-end">

        {{-- Search --}}
        <div class="lg:col-span-2">
            <label class="block text-xs font-medium text-gray-600 mb-1">Search</label>
            <input type="text" name="search" value="{{ request('search') }}"
                placeholder="Search title or description..."
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>

        {{-- Category --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
            <select name="category" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>
                        {{ $cat->label() }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Assigned To --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Assigned To</label>
            <select name="assigned_to" class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">All</option>
                @foreach ($users as $u)
                    <option value="{{ $u->id }}" {{ request('assigned_to') == $u->id ? 'selected' : '' }}>
                        {{ $u->full_name }}
                    </option>
                @endforeach
            </select>
        </div>

        {{-- Search button --}}
        <div class="flex gap-2">
            <button type="submit"
                class="flex-1 inline-flex items-center justify-center rounded-md bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
                Search
            </button>
            @if (request()->hasAny(['search', 'category', 'assigned_to', 'date_from', 'date_to']))
                <a href="{{ route('admin.tasks.archive') }}"
                    class="inline-flex items-center justify-center rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-600 hover:bg-gray-50">
                    Clear
                </a>
            @endif
        </div>

    </div>

    {{-- Date range row --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Completed From</label>
            <input type="date" name="date_from" value="{{ request('date_from') }}"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Completed To</label>
            <input type="date" name="date_to" value="{{ request('date_to') }}"
                class="w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
        </div>
    </div>
</form>

{{-- Results summary --}}
<div class="flex items-center justify-between mb-3">
    <p class="text-sm text-gray-500">
        {{ $tasks->total() }} completed {{ Str::plural('task', $tasks->total()) }} found
    </p>
</div>

{{-- Table --}}
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-600 whitespace-nowrap">Completed Date</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned To</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Equipment</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Created By</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Completed By</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($tasks as $task)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        {{ $task->completed_at?->format('M j, Y') ?? '—' }}
                        <div class="text-xs text-gray-400">{{ $task->completed_at?->format('g:i A') }}</div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->category->color() }}">
                            {{ $task->category->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 max-w-xs">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="font-medium text-gray-900 hover:text-brand-600 truncate block">
                            {{ $task->title }}
                        </a>
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $task->assignedTo?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($task->equipment)
                            <div class="text-sm text-gray-800 truncate max-w-[120px]">{{ $task->equipment->equipment_name }}</div>
                            <div class="text-xs text-gray-400">{{ $task->equipment->equipment_id }}</div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $task->createdBy?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $task->completedBy?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="text-blue-600 hover:underline text-xs">View</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-gray-400">No completed tasks found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

{{-- Pagination --}}
@if ($tasks->hasPages())
    <div class="mt-4">
        {{ $tasks->links() }}
    </div>
@endif

@endsection
