@extends('admin.layouts.app')

@section('title', 'Task Center')

@section('content')

@include('flash::message')

<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
    <div>
        <h3 class="text-xl font-semibold text-gray-800">Task Center</h3>
        <p class="text-sm text-gray-500 mt-1">Daily operational tasks for Sales, Yard, Shop, and Admin teams.</p>
    </div>
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.tasks.archive') }}"
            class="inline-flex items-center justify-center rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white shadow hover:bg-gray-800">
            Task Archive
        </a>
        <a href="{{ route('admin.tasks.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
            + New Task
        </a>
    </div>
</div>

@php
    // URL builder: merges current query params with overrides; null values are stripped (removes that param)
    $taskUrl = fn(array $overrides) => route('admin.tasks.index') . '?' . http_build_query(
        collect(request()->query())
            ->merge($overrides)
            ->filter(fn($v) => $v !== null && $v !== '')
            ->toArray()
    );

    // Active solid colors for each category
    $catActiveColor = [
        'sales' => 'bg-blue-600 text-white border-blue-600',
        'yard'  => 'bg-green-600 text-white border-green-600',
        'shop'  => 'bg-orange-500 text-white border-orange-500',
        'admin' => 'bg-purple-600 text-white border-purple-600',
    ];
    $badgeBase    = 'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium border transition-colors cursor-pointer';
    $badgeInactive = $badgeBase . ' bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200';
@endphp

{{-- Filters: single flex row — form wraps everything so hidden inputs are preserved on dropdown submit --}}
<form method="GET" action="{{ route('admin.tasks.index') }}"
      class="mb-5 flex flex-wrap items-end gap-x-3 gap-y-2">

    {{-- Preserve badge-selected filters when form dropdowns fire --}}
    @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
    @endif
    @if(request('assigned_to'))
        <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
    @endif

    {{-- Status --}}
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
        <select name="status" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            @foreach ($statuses as $st)
                <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Priority --}}
    <div>
        <label class="block text-xs font-medium text-gray-600 mb-1">Priority</label>
        <select name="priority" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.submit()">
            <option value="">All Priorities</option>
            @foreach ($priorities as $pri)
                <option value="{{ $pri->value }}" {{ request('priority') === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
            @endforeach
        </select>
    </div>

    {{-- Due Today / Overdue --}}
    <div class="flex items-center gap-3 pb-1">
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

    {{-- Divider --}}
    <span class="self-center h-5 w-px bg-gray-200 mx-1 shrink-0"></span>

    {{-- Task Categories badge group --}}
    <div class="flex items-center gap-2">
        <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Task Categories</span>

        <a href="{{ $taskUrl(['category' => null, 'page' => null]) }}"
           class="{{ !request('category') ? $badgeBase . ' bg-sky-600 text-white border-sky-600' : $badgeInactive }}">
            All ({{ $categoryCounts->sum() }})
        </a>

        @foreach ($categories as $cat)
            @if ($categoryCounts->has($cat->value))
                @php $isActive = request('category') === $cat->value; @endphp
                <a href="{{ $taskUrl(['category' => $cat->value, 'page' => null]) }}"
                   class="{{ $isActive ? $badgeBase . ' ' . ($catActiveColor[$cat->value] ?? 'bg-gray-600 text-white border-gray-600') : $badgeInactive }}">
                    {{ $cat->label() }} ({{ $categoryCounts->get($cat->value) }})
                </a>
            @endif
        @endforeach
    </div>

    {{-- Divider + Assigned To badge group --}}
    @if ($badgeUsers->isNotEmpty())
        <span class="self-center h-5 w-px bg-gray-200 mx-1 shrink-0"></span>

        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Assigned To</span>

            <a href="{{ $taskUrl(['assigned_to' => null, 'page' => null]) }}"
               class="{{ !request('assigned_to') ? $badgeBase . ' bg-emerald-600 text-white border-emerald-600' : $badgeInactive }}">
                All ({{ $userAllCount }})
            </a>

            @foreach ($badgeUsers as $bUser)
                @php $isActive = request('assigned_to') == $bUser->id; @endphp
                <a href="{{ $taskUrl(['assigned_to' => $bUser->id, 'page' => null]) }}"
                   class="{{ $isActive ? $badgeBase . ' bg-violet-600 text-white border-violet-600' : $badgeInactive }}">
                    {{ $bUser->full_name }} ({{ $userCountsRaw->get($bUser->id, 0) }})
                </a>
            @endforeach
        </div>
    @endif

</form>

{{-- Main layout: task table (left, 70%) + Completed Today widget (right, 30%) --}}
<div class="grid grid-cols-[7fr_3fr] gap-6 items-start">

    {{-- Task table --}}
    <div class="min-w-0">
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
                                    <div class="text-sm text-gray-800 truncate max-w-[120px]">{{ $task->equipment->equipment_name }}</div>
                                    <div class="text-xs text-gray-400">{{ $task->equipment->equipment_id }}</div>
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
    </div>

    {{-- Completed Today Widget --}}
    <div class="min-w-0">
        <div class="rounded-lg border border-emerald-200 bg-white shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b border-emerald-100 bg-emerald-50">
                <h4 class="text-sm font-semibold text-emerald-800">
                    Tasks Completed ({{ now()->format('F j, Y') }})
                </h4>
            </div>

            @if ($completedToday->isEmpty())
                <div class="px-4 py-6 text-center text-sm text-gray-400">
                    No tasks completed today.
                </div>
            @else
                <table class="min-w-full divide-y divide-gray-100 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Category</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Title</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500">Assigned To</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($completedToday as $ct)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 whitespace-nowrap">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $ct->category->color() }}">
                                        {{ $ct->category->label() }}
                                    </span>
                                </td>
                                <td class="px-3 py-2 max-w-[140px]">
                                    <span class="text-sm font-medium text-gray-800 truncate block">{{ $ct->title }}</span>
                                </td>
                                <td class="px-3 py-2 text-gray-600 whitespace-nowrap">
                                    {{ $ct->assignedTo?->full_name ?? '—' }}
                                </td>
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.tasks.show', $ct) }}"
                                        class="text-xs text-blue-600 hover:underline">View</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </div>

</div>

@endsection
