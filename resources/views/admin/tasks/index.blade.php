@extends('admin.layouts.app')

@section('title', 'Task Center')

@section('content')

@include('flash::message')

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <div>
        <h3 class="text-xl font-semibold text-gray-800">Task Center</h3>
        <p class="text-sm text-gray-500 mt-1">Daily operational tasks for Sales, Yard, Shop, and Admin teams.</p>
    </div>
    <a href="{{ route('admin.tasks.create') }}"
        class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
        + New Task
    </a>
</div>

{{-- Filters --}}
<form method="GET" action="{{ route('admin.tasks.index') }}" class="mb-5">
    <div class="flex flex-wrap gap-3 items-end">

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Category</label>
            <select name="category" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
                <option value="">All Categories</option>
                @foreach ($categories as $cat)
                    <option value="{{ $cat->value }}" {{ request('category') === $cat->value ? 'selected' : '' }}>{{ $cat->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Priority</label>
            <select name="priority" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
                <option value="">All Priorities</option>
                @foreach ($priorities as $pri)
                    <option value="{{ $pri->value }}" {{ request('priority') === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Assigned To</label>
            <select name="assigned_to" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
                <option value="">All Users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ request('assigned_to') == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="flex items-center gap-4 pb-1">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="due_today" value="1" {{ request('due_today') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-gray-300 text-brand-500">
                Due Today
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }} onchange="this.form.submit()" class="rounded border-gray-300 text-red-500">
                Overdue
            </label>
        </div>

        @if (request()->hasAny(['category', 'status', 'priority', 'assigned_to', 'due_today', 'overdue']))
            <a href="{{ route('admin.tasks.index') }}" class="text-sm text-gray-500 hover:text-gray-700 underline pb-1">Clear filters</a>
        @endif

    </div>
</form>

{{-- Table --}}
<div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-gray-200 text-sm">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned To</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Equipment</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Priority</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Status</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Due Date</th>
                <th class="px-4 py-3 text-left font-medium text-gray-600">Created By</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @forelse ($tasks as $task)
                <tr class="hover:bg-gray-50 {{ $task->isOverdue() ? 'bg-red-50/40' : '' }}">
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->category->color() }}">
                            {{ $task->category->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 max-w-xs">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="font-medium text-gray-900 hover:text-brand-600 truncate block">
                            {{ $task->title }}
                        </a>
                        @if ($task->isOverdue())
                            <span class="text-xs text-red-600 font-medium">Overdue</span>
                        @endif
                    </td>
                    <td class="px-4 py-3 text-gray-700">
                        {{ $task->assignedTo?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if ($task->equipment)
                            <span class="font-medium text-gray-800 text-xs">{{ $task->equipment->equipment_id }}</span>
                            <div class="text-xs text-gray-500 truncate max-w-[120px]">{{ $task->equipment->equipment_name }}</div>
                        @else
                            <span class="text-gray-400">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->priority->color() }}">
                            {{ $task->priority->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->status->color() }}">
                            {{ $task->status->label() }}
                        </span>
                    </td>
                    <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                        {{ $task->due_date?->format('M j, Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-gray-600">
                        {{ $task->createdBy?->full_name ?? '—' }}
                    </td>
                    <td class="px-4 py-3 text-right whitespace-nowrap">
                        <a href="{{ route('admin.tasks.show', $task) }}" class="text-blue-600 hover:underline text-xs mr-3">View</a>
                        <a href="{{ route('admin.tasks.edit', $task) }}" class="text-gray-600 hover:underline text-xs">Edit</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-gray-400">No tasks found.</td>
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
