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
        'sales'   => 'bg-blue-600 text-white border-blue-600',
        'admin'   => 'bg-purple-600 text-white border-purple-600',
        'billing' => 'bg-teal-600 text-white border-teal-500',
        'yard'    => 'bg-green-600 text-white border-green-600',
        'shop'    => 'bg-orange-500 text-white border-orange-500',
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

    {{-- Task Center board — one lane per person (grouped by assignee), cards
         within each lane keep the due-date → priority sort. --}}
    <div id="task-list-zone" style="flex:7.5; min-width:0;">

        @if (request('assigned_to') && $lanes)
            <div class="mb-4">
                <a href="{{ $taskUrl(['assigned_to' => null, 'page' => null]) }}"
                   class="inline-flex items-center gap-1.5 rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                    <x-heroicon-o-arrow-left class="w-4 h-4" /> All people
                </a>
            </div>
        @endif

        <div style="display:flex; flex-wrap:wrap; gap:18px; align-items:flex-start;">
            @forelse ($lanes as $lane)
                <div style="flex:1 1 340px; min-width:320px; max-width:100%; background:#f8fafc; border:1px solid #eef2f6; border-radius:16px; padding:6px; display:flex; flex-direction:column;">

                    {{-- Lane header — click to focus this person (reuses the assigned_to filter) --}}
                    @php
                        $laneFocusable = $lane['user_id'] && !request('assigned_to');
                        $laneHeadStyle = "display:flex; align-items:center; gap:11px; padding:15px 15px 13px; border-bottom:3px solid {$lane['color']}; background:{$lane['bg']}; border-radius:12px 12px 0 0;";
                    @endphp
                    @if ($laneFocusable)
                        <a href="{{ $taskUrl(['assigned_to' => $lane['user_id'], 'page' => null]) }}"
                           style="{{ $laneHeadStyle }} cursor:pointer; text-decoration:none;">
                    @else
                        <div style="{{ $laneHeadStyle }}">
                    @endif
                        <span style="width:38px; height:38px; flex:0 0 auto; border-radius:50%; background:#fff; color:{{ $lane['color'] }}; border:1px solid {{ $lane['color'] }}; display:flex; align-items:center; justify-content:center; font-weight:700; font-size:14px;">{{ $lane['initials'] }}</span>
                        <div style="min-width:0;">
                            <div style="font-size:15px; font-weight:700; color:#0f172a;">{{ $lane['name'] }}</div>
                            @if ($lane['role'])
                                <div style="font-size:12px; color:#64748b; margin-top:1px;">{{ $lane['role'] }}</div>
                            @endif
                        </div>
                        <span style="margin-left:auto; font-size:12px; font-weight:600; color:{{ $lane['color'] }}; background:#fff; border:1px solid {{ $lane['color'] }}; border-radius:999px; padding:4px 11px; white-space:nowrap;">
                            {{ $lane['count'] }} {{ \Illuminate\Support\Str::plural('task', $lane['count']) }}
                        </span>
                        @if ($laneFocusable)
                            <span style="margin-left:6px; color:{{ $lane['color'] }}; font-size:20px; font-weight:700; line-height:1;">&rsaquo;</span>
                        @endif
                    @if ($laneFocusable)
                        </a>
                    @else
                        </div>
                    @endif

                    {{-- Cards --}}
                    <div style="display:flex; flex-direction:column; gap:10px; padding:12px;">
                        @foreach ($lane['items'] as $item)
                            @if ($item->type === 'call')
                                @php $call = $item->model; $callOverdue = $call->isOverdue(); @endphp
                                <a href="{{ route('admin.tasks.call.show', $call->id) }}"
                                   style="display:block; text-decoration:none; background:#fff; border:1px solid #e9edf2; border-left:4px solid {{ $call->priority->stripeColor() }}; border-radius:11px; padding:14px 16px; box-shadow:0 1px 2px rgba(15,23,42,.04);">
                                    <div class="flex items-center gap-1.5 mb-2.5 flex-wrap">
                                        @if ($call->category)
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $call->category->color() }}">{{ $call->category->label() }}</span>
                                        @else
                                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-sky-100 text-sky-700">Call</span>
                                        @endif
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $call->priority->color() }}">{{ $call->priority->label() }}</span>
                                    </div>
                                    <div style="font-size:15px; font-weight:600; color:#0f172a; margin-bottom:10px;">{{ \App\Helpers\CustomHelper::formatCallReason($call->reason) }}</div>
                                    @if ($call->customer)
                                        <div class="flex items-center gap-1.5 mb-1.5" style="font-size:12.5px; color:#64748b;">
                                            <span style="color:#94a3b8;">Customer</span>
                                            <span style="font-weight:600; color:#334155;">{{ $call->customer->full_name }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between" style="margin-top:12px; padding-top:11px; border-top:1px solid #f1f5f9;">
                                        <span style="font-size:12.5px; font-weight:600; color:{{ $callOverdue ? '#dc2626' : '#64748b' }};">
                                            {{ $call->due_date ? $call->due_date->format('M j, Y') : 'No due date' }}{{ $callOverdue ? ' · Overdue' : '' }}
                                        </span>
                                        <span style="font-size:12.5px; font-weight:600; color:#0d9488; margin-left:auto;">View task &rarr;</span>
                                    </div>
                                </a>
                            @else
                                @php $task = $item->model; $taskOverdue = $task->isOverdue(); @endphp
                                <a href="{{ route('admin.tasks.show', $task) }}"
                                   style="display:block; text-decoration:none; background:#fff; border:1px solid #e9edf2; border-left:4px solid {{ $task->priority->stripeColor() }}; border-radius:11px; padding:14px 16px; box-shadow:0 1px 2px rgba(15,23,42,.04);">
                                    <div class="flex items-center gap-1.5 mb-2.5 flex-wrap">
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->category->color() }}">{{ $task->category->label() }}</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
                                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->status->color() }}">{{ $task->status->label() }}</span>
                                    </div>
                                    <div style="font-size:15px; font-weight:600; color:#0f172a; margin-bottom:10px;">{{ $task->title }}</div>
                                    @if ($task->customer || $task->order)
                                        <div class="flex items-center gap-1.5 mb-1.5" style="font-size:12.5px; color:#64748b;">
                                            <span style="color:#94a3b8;">Customer</span>
                                            <span style="font-weight:600; color:#334155;">{{ $task->customer?->full_name ?? '—' }}</span>
                                            @if ($task->order)
                                                <span style="color:#0d9488; font-weight:600;">{{ $task->order->order_number }}</span>
                                            @endif
                                        </div>
                                    @endif
                                    @if ($task->equipment)
                                        <div class="flex items-center gap-1.5 mb-1.5" style="font-size:12.5px; color:#64748b;">
                                            <span style="color:#94a3b8;">Equipment</span>
                                            <span style="font-weight:600; color:#334155;">{{ $task->equipment->equipment_name }}</span>
                                            <span style="color:#94a3b8;">{{ $task->equipment->equipment_id }}</span>
                                        </div>
                                    @endif
                                    <div class="flex items-center justify-between" style="margin-top:12px; padding-top:11px; border-top:1px solid #f1f5f9;">
                                        <span style="font-size:12.5px; font-weight:600; color:{{ $taskOverdue ? '#dc2626' : '#64748b' }};">
                                            {{ $task->due_date ? $task->due_date->format('M j, Y') : 'No due date' }}{{ $taskOverdue ? ' · Overdue' : '' }}
                                        </span>
                                        <span style="font-size:12.5px; font-weight:600; color:#0d9488; margin-left:auto;">View task &rarr;</span>
                                    </div>
                                </a>
                            @endif
                        @endforeach

                        <button type="button" onclick="openNewTaskModal()"
                            style="border:1px dashed #cbd5e1; background:transparent; border-radius:11px; padding:11px; font-family:inherit; font-size:13px; font-weight:600; color:#94a3b8; cursor:pointer;">
                            + Add task
                        </button>
                    </div>
                </div>
            @empty
                <div class="w-full rounded-lg border border-gray-200 bg-white shadow-sm px-4 py-16 text-center text-gray-400">
                    No tasks match these filters.
                </div>
            @endforelse
        </div>
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

{{-- Task manager mode flag + unified New Task modal.
     The call modal partial stays included for the Edit / Complete /
     Reschedule flows on call rows — only its create entry point is gone. --}}
<script>window.taskManagerMode = true;</script>
@include('admin.tasks.partials._unified_task_modal')
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
