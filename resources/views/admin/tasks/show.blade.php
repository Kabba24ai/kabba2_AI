@extends('admin.layouts.app')

@section('title', 'Task Detail')

@section('content')

@include('flash::message')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-5 h-5" />
    </a>
    <h3 class="text-xl font-semibold text-gray-800">Task Detail</h3>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    {{-- Main --}}
    <div class="lg:col-span-2 space-y-5">

        {{-- Task Card --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <div class="flex items-start justify-between mb-4">
                <div class="flex-1">
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->category->color() }}">
                            {{ $task->category->label() }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->priority->color() }}">
                            {{ $task->priority->label() }}
                        </span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $task->status->color() }}">
                            {{ $task->status->label() }}
                        </span>
                        @if ($task->isOverdue())
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium bg-red-100 text-red-700">
                                Overdue
                            </span>
                        @endif
                    </div>
                    <h2 class="text-lg font-semibold text-gray-900">{{ $task->title }}</h2>
                </div>
                <div class="ml-4 flex gap-2">
                    <a href="{{ route('admin.tasks.edit', $task) }}"
                        class="inline-flex items-center rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('admin.tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="inline-flex items-center rounded-md border border-red-200 px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50">
                            Delete
                        </button>
                    </form>
                </div>
            </div>

            @if ($task->description)
                <p class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ $task->description }}</p>
            @else
                <p class="text-sm text-gray-400 italic">No description provided.</p>
            @endif
        </div>

        {{-- Comments --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Comments ({{ $task->comments->count() }})</h4>

            <div class="space-y-4 mb-5">
                @forelse ($task->comments as $comment)
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-brand-100 flex items-center justify-center text-brand-700 text-xs font-bold shrink-0">
                            {{ strtoupper(substr($comment->user?->first_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-800">{{ $comment->user?->full_name ?? 'Unknown' }}</span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $comment->comment }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No comments yet.</p>
                @endforelse
            </div>

            <form id="comment-form" method="POST" action="{{ route('admin.tasks.comments.store', $task) }}">
                @csrf
                <textarea id="task-comment" name="comment" rows="3" placeholder="Add a comment..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 mb-3"></textarea>

                <div id="comment-validation-msg" class="hidden mb-2 text-sm text-red-600 font-medium">
                    Please enter a completion note before marking this task complete.
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="inline-flex items-center rounded-lg bg-brand-500 px-4 py-2 text-sm font-medium text-white shadow hover:bg-brand-600">
                        Add Comment
                    </button>

                    @if (!$task->status->isTerminal())
                        <button type="submit"
                            formaction="{{ route('admin.tasks.complete', $task) }}"
                            onclick="return validateAndComplete()"
                            class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-emerald-700">
                            Add Comment &amp; Mark Complete
                        </button>
                    @endif
                </div>
            </form>

            <script>
            function validateAndComplete() {
                var comment = document.getElementById('task-comment').value.trim();
                var msg = document.getElementById('comment-validation-msg');
                if (!comment) {
                    msg.classList.remove('hidden');
                    document.getElementById('task-comment').focus();
                    return false;
                }
                msg.classList.add('hidden');
                return true;
            }
            </script>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="space-y-5">

        {{-- Equipment Panel --}}
        @if ($task->equipment)
        <div class="bg-white rounded-lg border border-brand-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <x-heroicon-o-wrench-screwdriver class="w-4 h-4 text-brand-500" />
                Equipment
            </h4>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Equipment ID</dt>
                    <dd class="font-semibold text-gray-900">{{ $task->equipment->equipment_id }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Name</dt>
                    <dd class="font-medium text-gray-800 text-right max-w-[160px]">{{ $task->equipment->equipment_name }}</dd>
                </div>
                @if ($task->equipment->productCategory)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Category</dt>
                    <dd class="font-medium text-gray-800">{{ $task->equipment->productCategory->title }}</dd>
                </div>
                @endif
                @if ($task->equipment->serial_number)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Serial #</dt>
                    <dd class="font-medium text-gray-800">{{ $task->equipment->serial_number }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Status</dt>
                    <dd>
                        @php
                            $eqColor = match($task->equipment->current_status?->value) {
                                'available'   => 'bg-green-100 text-green-700',
                                'rented'      => 'bg-blue-100 text-blue-700',
                                'maintenance' => 'bg-yellow-100 text-yellow-700',
                                'damaged'     => 'bg-red-100 text-red-700',
                                default       => 'bg-gray-100 text-gray-600',
                            };
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $eqColor }}">
                            {{ $task->equipment->status_label }}
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
        @endif

        {{-- Details --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Details</h4>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Assigned To</dt>
                    <dd class="font-medium text-gray-800">{{ $task->assignedTo?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created By</dt>
                    <dd class="font-medium text-gray-800">{{ $task->createdBy?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Due Date</dt>
                    <dd class="font-medium {{ $task->isOverdue() ? 'text-red-600' : 'text-gray-800' }}">
                        {{ $task->due_date?->format('M j, Y g:i A') ?? '—' }}
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Completed At</dt>
                    <dd class="font-medium text-gray-800">{{ $task->completed_at?->format('M j, Y g:i A') ?? '—' }}</dd>
                </div>
                @if ($task->completedBy)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Completed By</dt>
                    <dd class="font-medium text-gray-800">{{ $task->completedBy->full_name }}</dd>
                </div>
                @endif
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="font-medium text-gray-800">{{ $task->created_at->format('M j, Y') }}</dd>
                </div>
            </dl>
        </div>

        {{-- Activity Log --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Activity</h4>
            <div class="space-y-3">
                @forelse ($task->activityLogs as $log)
                    <div class="flex gap-2 text-xs">
                        <div class="w-1.5 h-1.5 rounded-full bg-gray-400 mt-1.5 shrink-0"></div>
                        <div>
                            <span class="text-gray-800 font-medium">{{ str_replace('_', ' ', ucfirst($log->action)) }}</span>
                            @if ($log->old_value && $log->new_value)
                                <span class="text-gray-500"> — {{ $log->old_value }} → {{ $log->new_value }}</span>
                            @elseif ($log->new_value)
                                <span class="text-gray-500"> — {{ $log->new_value }}</span>
                            @endif
                            <div class="text-gray-400 mt-0.5">{{ $log->user?->full_name ?? 'System' }} · {{ $log->created_at->diffForHumans() }}</div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-gray-400">No activity recorded.</p>
                @endforelse
            </div>
        </div>

    </div>
</div>

@endsection
