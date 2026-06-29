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
        <button
            type="button"
            onclick="openCallNeededModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-red-700">
            <x-heroicon-o-phone class="w-4 h-4" />
            Call Needed
        </button>
        <button type="button" onclick="openNewTaskModal()"
            class="inline-flex items-center justify-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
            + New Task
        </button>
        <a href="{{ route('admin.tasks.archive') }}"
            class="inline-flex items-center justify-center rounded-lg bg-gray-700 px-4 py-2 text-sm font-medium text-white shadow hover:bg-gray-800">
            Task Archive
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

    $catActiveColor = [
        'sales' => 'bg-blue-600 text-white border-blue-600',
        'yard'  => 'bg-green-600 text-white border-green-600',
        'shop'  => 'bg-orange-500 text-white border-orange-500',
        'admin' => 'bg-purple-600 text-white border-purple-600',
    ];
    $badgeBase     = 'inline-flex items-center rounded-full px-3 py-1 text-sm font-medium border transition-colors cursor-pointer';
    $badgeInactive = $badgeBase . ' bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200';

    $typeParam  = request('type') ?: 'all';
    $showTasks  = $typeParam !== 'calls';
    $showCalls  = $typeParam !== 'tasks';
@endphp

{{-- Filter bar + widget title: flex row, stretch-aligned so title sits at badge-row level --}}
<div class="mb-5" style="display:flex; gap:1.5rem; align-items:stretch;">
<div style="flex:7.5; min-width:0;">
<form id="task-filter-zone" method="GET" action="{{ route('admin.tasks.index') }}" class="space-y-4">

    {{-- Hidden: preserve badge-selected filters when form dropdowns submit --}}
    @if(request('category'))
        <input type="hidden" name="category" value="{{ request('category') }}">
    @endif
    @if(request('assigned_to'))
        <input type="hidden" name="assigned_to" value="{{ request('assigned_to') }}">
    @endif

    {{-- Row 1: Status | Priority | Type | Due Today | Overdue | Clear --}}
    <div class="flex flex-wrap items-end gap-x-3 gap-y-2 pb-[7px]">

        {{-- Status --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.requestSubmit()">
                <option value="">All Statuses</option>
                @foreach ($statuses as $st)
                    <option value="{{ $st->value }}" {{ request('status') === $st->value ? 'selected' : '' }}>{{ $st->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Priority --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Priority</label>
            <select name="priority" class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500" onchange="this.form.requestSubmit()">
                <option value="">All Priorities</option>
                @foreach ($priorities as $pri)
                    <option value="{{ $pri->value }}" {{ request('priority') === $pri->value ? 'selected' : '' }}>{{ $pri->label() }}</option>
                @endforeach
            </select>
        </div>

        {{-- Type 3-way toggle --}}
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
            <input type="hidden" name="type" id="task-type-val" value="{{ request('type', '') }}">
            <div class="inline-flex items-center bg-gray-100 rounded-lg p-0.5 gap-0.5">
                <button type="button"
                    onclick="document.getElementById('task-type-val').value=''; this.closest('form').requestSubmit()"
                    class="{{ $typeParam === 'all' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 font-medium' }} px-3 py-1.5 text-sm rounded-md transition-colors whitespace-nowrap">
                    All ({{ $taskCount + $callCount }})
                </button>
                <button type="button"
                    onclick="document.getElementById('task-type-val').value='tasks'; this.closest('form').requestSubmit()"
                    class="{{ $typeParam === 'tasks' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 font-medium' }} px-3 py-1.5 text-sm rounded-md transition-colors whitespace-nowrap">
                    Tasks ({{ $taskCount }})
                </button>
                <button type="button"
                    onclick="document.getElementById('task-type-val').value='calls'; this.closest('form').requestSubmit()"
                    class="{{ $typeParam === 'calls' ? 'bg-white shadow-sm text-gray-900 font-semibold' : 'text-gray-500 hover:text-gray-700 font-medium' }} px-3 py-1.5 text-sm rounded-md transition-colors whitespace-nowrap">
                    Calls ({{ $callCount }})
                </button>
            </div>
        </div>

        {{-- Due Today / Overdue --}}
        <div class="flex items-center gap-3 pb-1">
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="due_today" value="1" {{ request('due_today') ? 'checked' : '' }} onchange="this.form.requestSubmit()" class="rounded border-gray-300 text-brand-500">
                Due Today
            </label>
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer">
                <input type="checkbox" name="overdue" value="1" {{ request('overdue') ? 'checked' : '' }} onchange="this.form.requestSubmit()" class="rounded border-gray-300 text-red-500">
                Overdue
            </label>
        </div>

        {{-- Clear filters --}}
        @if (request()->hasAny(['category', 'status', 'priority', 'assigned_to', 'due_today', 'overdue', 'type']))
            <a href="{{ route('admin.tasks.index') }}"
               class="inline-flex items-center gap-1.5 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm hover:bg-gray-50 hover:text-gray-800">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
                Clear filters
            </a>
        @endif

    </div>

    {{-- Row 2: Task Categories + Assigned To badge groups --}}
    <div class="flex flex-wrap items-center gap-x-2 gap-y-2">

        {{-- Task Categories --}}
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

        {{-- Assigned To badge group --}}
        @if ($badgeUsers->isNotEmpty())
            <span class="self-center h-5 w-px bg-gray-200 mx-1 shrink-0"></span>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide shrink-0">Assigned To</span>

                <a href="{{ $taskUrl(['assigned_to' => null, 'page' => null]) }}"
                   class="{{ !request('assigned_to') ? $badgeBase . ' bg-emerald-600 text-white border-emerald-600' : $badgeInactive }}">
                    All ({{ $userAllCount }})
                </a>

                @foreach ($badgeUsers as $bUser)
                    @php $isActiveUser = request('assigned_to') == $bUser->id; @endphp
                    <a href="{{ $taskUrl(['assigned_to' => $bUser->id, 'page' => null]) }}"
                       class="{{ $badgeBase }} {{ $isActiveUser ? 'border-emerald-300' : 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200' }}"
                       style="{{ $isActiveUser ? 'background-color:#d1fae5; color:#065f46;' : '' }}">
                        {{ $bUser->full_name }} ({{ $userCountsRaw->get($bUser->id, 0) }})
                    </a>
                @endforeach
            </div>
        @endif

    </div>

</form>
</div>{{-- /filter left col --}}
<div style="flex:2.5; min-width:0; display:flex; flex-direction:column; justify-content:flex-end;">
    <p class="text-sm font-semibold text-gray-700 text-center">Tasks Completed - {{ now()->format('F j, Y') }}</p>
</div>{{-- /title right col --}}
</div>{{-- /filter+title row --}}

{{-- Main layout: task table (75%) + Completed Today widget (25%) --}}
<div style="display:flex; gap:1.5rem; align-items:flex-start;">

    {{-- Task / Call table --}}
    <div id="task-list-zone" style="flex:7.5; min-width:0;">
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-3 w-8"></th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 w-24">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Title / Reason</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 w-36">Assigned To</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Equipment</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Customer</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Supplier / Other</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 w-28 whitespace-nowrap">Due Date</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 w-36 whitespace-nowrap">Created By</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 w-24">Priority</th>
                        <th class="px-4 py-3 w-16"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    {{-- Unified task + call list sorted by due date then priority --}}
                    @forelse ($tasks as $item)
                        @if ($item->type === 'call')
                            @php
                                $call = $item->model;
                                $contactType   = $call->customer_id ? 'customer' : ($call->supplier_id ? 'supplier' : 'other');
                                $customerName  = $contactType === 'customer' ? $call->customer?->full_name : null;
                                $supplierOther = match($contactType) {
                                    'supplier' => $call->supplier?->name,
                                    'other'    => $call->contact_name,
                                    default    => null,
                                };
                                $callIsOverdue = $call->due_date && $call->due_date->lt(today());
                            @endphp
                            <tr class="{{ $callIsOverdue ? 'bg-red-50/40 hover:bg-red-50/50' : 'hover:bg-red-50/20 bg-red-50/10' }}">
                                <td class="px-3 py-3 text-center">
                                    <span title="Call Reminder">
                                        <x-heroicon-o-phone class="w-4 h-4 text-red-500 mx-auto" />
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($call->category)
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $call->category->color() }}">
                                            {{ $call->category->label() }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400 italic">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 max-w-xs">
                                    <a href="{{ route('admin.tasks.call.show', $call->id) }}"
                                       class="font-medium text-gray-900 hover:text-brand-600 truncate block">
                                        {{ ucwords(str_replace('_', ' ', $call->reason)) }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $call->assignee?->full_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $customerName ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $supplierOther ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    @if ($call->due_date)
                                        <span class="{{ $callIsOverdue ? 'text-red-600 font-medium' : '' }}">
                                            {{ $call->due_date->format('M j, Y g:i A') }}
                                        </span>
                                        @if ($callIsOverdue)
                                            <span class="block text-xs text-red-600 font-medium">Overdue</span>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    {{ $call->creator?->full_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $call->priority->color() }}">
                                        {{ $call->priority->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.tasks.call.show', $call->id) }}" class="text-blue-600 hover:underline text-xs">View</a>
                                </td>
                            </tr>
                        @else
                            @php $task = $item->model; @endphp
                            <tr class="hover:bg-gray-50 {{ $task->isOverdue() ? 'bg-red-50/40' : '' }}">
                                <td class="px-3 py-3 text-center">
                                    <span title="Task">
                                        <x-heroicon-o-clipboard-document-list class="w-4 h-4 text-gray-400 mx-auto" />
                                    </span>
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
                                    <span class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="text-gray-400">—</span>
                                </td>
                                <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                    {{ $task->due_date?->format('M j, Y') ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-gray-600">
                                    {{ $task->createdBy?->full_name ?? '—' }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->priority->color() }}">
                                        {{ $task->priority->label() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.tasks.show', $task) }}" class="text-blue-600 hover:underline text-xs">View</a>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-10 text-center text-gray-400">No tasks found.</td>
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
    <div style="flex:2.5; min-width:0;">
        <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Category</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Title</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600">Assigned To</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($completedToday as $ct)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $ct->category->color() }}">
                                    {{ $ct->category->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-800 truncate block">{{ $ct->title }}</span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ $ct->assignedTo?->full_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.tasks.show', $ct) }}"
                                    class="text-xs text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @empty
                    @endforelse

                    @foreach ($callsCompletedToday as $cc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($cc->category)
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $cc->category->color() }}">
                                        {{ $cc->category->label() }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-sky-100 text-sky-700">
                                        Call
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="text-sm font-medium text-gray-800 truncate block">
                                    {{ \App\Helpers\CustomHelper::formatCallReason($cc->reason) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                                {{ $cc->assignee?->full_name ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('admin.tasks.call.show', $cc->id) }}"
                                    class="text-xs text-blue-600 hover:underline">View</a>
                            </td>
                        </tr>
                    @endforeach

                    @if ($completedToday->isEmpty() && $callsCompletedToday->isEmpty())
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-gray-400">No tasks completed today.</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>

</div>

{{-- Task manager mode flag + shared Call Needed modal --}}
<script>window.taskManagerMode = true;</script>
@include('admin.tasks.partials._new_task_modal')
@include('admin.dashboard.partials._call_needed_modal')

@push('js')
<script>
(function () {
    'use strict';

    var _fetching = false;

    async function doFilter(url) {
        if (_fetching) return;
        _fetching = true;

        var listZone = document.getElementById('task-list-zone');
        if (listZone) {
            listZone.style.opacity = '0.45';
            listZone.style.pointerEvents = 'none';
        }

        try {
            var res = await fetch(url.toString(), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);

            var html = await res.text();
            var doc  = new DOMParser().parseFromString(html, 'text/html');

            var newFilter = doc.getElementById('task-filter-zone');
            var newList   = doc.getElementById('task-list-zone');

            if (newFilter) document.getElementById('task-filter-zone').replaceWith(newFilter);
            if (newList)   document.getElementById('task-list-zone').replaceWith(newList);

            history.pushState(null, '', url.toString());

        } catch (err) {
            console.error('Task filter error:', err);
            window.location.href = url.toString();
        } finally {
            _fetching = false;
            var zone = document.getElementById('task-list-zone');
            if (zone) {
                zone.style.opacity = '';
                zone.style.pointerEvents = '';
            }
        }
    }

    function buildFormUrl(form) {
        var params = new URLSearchParams();
        new FormData(form).forEach(function (v, k) {
            if (v !== '' && v !== null) params.append(k, v);
        });
        var qs = params.toString();
        return new URL(form.action + (qs ? '?' + qs : ''));
    }

    // Dropdown / checkbox changes — requestSubmit() fires the submit event so we can intercept it
    document.addEventListener('submit', function (e) {
        if (e.target.id !== 'task-filter-zone') return;
        e.preventDefault();
        doFilter(buildFormUrl(e.target));
    });

    // Badge link clicks (filter zone) and pagination link clicks (list zone)
    document.addEventListener('click', function (e) {
        var filterLink = e.target.closest('#task-filter-zone a[href]');
        if (filterLink) {
            e.preventDefault();
            doFilter(new URL(filterLink.href));
            return;
        }

        var listLink = e.target.closest('#task-list-zone a[href]');
        if (listLink) {
            // Only intercept pagination — links that point back to the same page path
            var filterForm = document.getElementById('task-filter-zone');
            var basePath   = filterForm ? new URL(filterForm.action).pathname : null;
            if (basePath && new URL(listLink.href).pathname === basePath) {
                e.preventDefault();
                doFilter(new URL(listLink.href));
            }
        }
    });

    // Browser back / forward
    window.addEventListener('popstate', function () {
        doFilter(new URL(window.location.href));
    });
}());
</script>
@endpush

@endsection
