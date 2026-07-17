@php
    $avatarPalette = [
        'bg-orange-100 text-orange-700',
        'bg-teal-100 text-teal-700',
        'bg-blue-100 text-blue-700',
        'bg-purple-100 text-purple-700',
        'bg-green-100 text-green-700',
        'bg-rose-100 text-rose-700',
        'bg-amber-100 text-amber-700',
        'bg-indigo-100 text-indigo-700',
    ];
@endphp

<div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-5 h-full">

    {{-- Header --}}
    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">

        <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-lg bg-red-100 flex items-center justify-center">
                <x-heroicon-o-bell-alert class="w-5 h-5 text-red-600" />
            </div>
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Task Manager Alerts</h2>
                <p class="text-sm text-gray-500">Team members with pending calls or tasks</p>
            </div>
        </div>

        {{-- KEY --}}
        <div class="flex items-center gap-3 text-xs text-gray-500 font-medium">
            <span>KEY:</span>
            <span class="inline-flex items-center gap-1 text-sky-600">
                <x-heroicon-o-phone class="w-3.5 h-3.5" /> Calls
            </span>
            <span class="inline-flex items-center gap-1 text-green-600">
                <x-heroicon-o-check-circle class="w-3.5 h-3.5" /> Tasks
            </span>
        </div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-3">
            <button
                type="button"
                onclick="openCallNeededModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-red-600 text-white text-sm font-medium shadow-sm hover:bg-red-700 transition-all">
                <x-heroicon-o-phone class="w-4 h-4" />
                Call Needed
            </button>
            <button
                type="button"
                onclick="openNewTaskModal()"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-brand-500 text-white text-sm font-medium shadow-sm hover:bg-brand-600 transition-all">
                + New Task
            </button>
        </div>

    </div>

    {{-- Employee Cards --}}
    @if ($teamWorkload->isEmpty())
        <div class="text-center py-10">
            <x-heroicon-o-check-circle class="w-12 h-12 mx-auto mb-3 text-gray-300" />
            <p class="font-medium text-gray-500">No outstanding team activities.</p>
            <p class="text-sm text-gray-400 mt-1">There are currently no open tasks or call reminders assigned to your team.</p>
        </div>
    @else
        <div class="flex flex-wrap gap-4">
            @foreach ($teamWorkload as $index => $entry)
                @php
                    $user     = $entry['user'];
                    $initials = $user
                        ? strtoupper(substr($user->first_name ?? '', 0, 1) . substr($user->last_name ?? '', 0, 1))
                        : '?';
                    $avatarClass = $avatarPalette[$index % count($avatarPalette)];
                @endphp
                <a href="{{ route('admin.tasks.index') }}?assigned_to={{ $user?->id }}"
                    class="flex flex-col items-center gap-2 bg-gray-50 border border-gray-200 rounded-xl px-5 py-4 hover:shadow-md hover:border-gray-300 transition-all min-w-[130px]">

                    {{-- Avatar --}}
                    <div class="w-12 h-12 rounded-full flex items-center justify-center text-base font-bold {{ $avatarClass }}">
                        {{ $initials }}
                    </div>

                    {{-- Name --}}
                    <span class="text-sm font-semibold text-gray-800 text-center leading-tight">
                        {{ $user?->full_name ?? 'Unassigned' }}
                    </span>

                    {{-- Counts --}}
                    <div class="flex items-center gap-3 text-xs font-medium">
                        <span class="inline-flex items-center gap-1 text-sky-600">
                            <x-heroicon-o-phone class="w-3.5 h-3.5" /> {{ $entry['call_count'] }}
                        </span>
                        <span class="inline-flex items-center gap-1 text-green-600">
                            <x-heroicon-o-check-circle class="w-3.5 h-3.5" /> {{ $entry['task_count'] }}
                        </span>
                    </div>

                    {{-- Priority badges --}}
                    @if ($entry['urgent_count'] > 0 || $entry['overdue_count'] > 0)
                        <div class="flex flex-wrap justify-center gap-1">
                            @if ($entry['urgent_count'] > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-700">
                                    {{ $entry['urgent_count'] }} Urgent
                                </span>
                            @endif
                            @if ($entry['overdue_count'] > 0)
                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium bg-orange-100 text-orange-700">
                                    {{ $entry['overdue_count'] }} Overdue
                                </span>
                            @endif
                        </div>
                    @endif

                </a>
            @endforeach
        </div>
    @endif

</div>

@include('admin.tasks.partials._new_task_modal')
@include('admin.dashboard.partials._call_needed_modal')
