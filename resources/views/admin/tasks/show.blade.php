@extends('admin.layouts.app', ['contentClass' => 'max-w-(--breakpoint-2xl)'])

@section('title', 'Task Detail')

@section('content')

@include('flash::message')

<div class="flex items-center gap-3 mb-6">
    <a href="{{ route('admin.tasks.index') }}" class="text-gray-500 hover:text-gray-700">
        <x-heroicon-o-arrow-left class="w-5 h-5" />
    </a>
    <h3 class="text-xl font-semibold text-gray-800">Task Detail</h3>
</div>

{{-- Fluid two-column: main takes remaining width, sidebar a fixed 320px,
     stacking beneath the main content below the xl breakpoint. Width and
     centering come from the layout's canonical container (contentClass). --}}
<div class="flex flex-col xl:flex-row gap-6">

    {{-- Main --}}
    <div class="flex-1 min-w-0 space-y-5">

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

            @if ($task->descriptionMedia->isNotEmpty())
                <div class="mt-4 pt-4 border-t border-gray-100">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Attachments</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach ($task->descriptionMedia as $media)
                            @if ($media->isImage())
                                <a href="{{ $media->url }}" target="_blank" title="{{ $media->original_filename }}">
                                    <img src="{{ $media->url }}" alt="{{ $media->original_filename }}"
                                        class="h-24 w-24 object-cover rounded-lg border border-gray-200 hover:opacity-80 transition">
                                </a>
                            @else
                                <video controls preload="metadata" title="{{ $media->original_filename }}"
                                    class="h-24 rounded-lg border border-gray-200 bg-black">
                                    <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                </video>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Status control — segmented bar, free movement between the four
                 working states (Task Status Bar design). Completed/cancelled
                 tasks keep their badge; the control is hidden for them. --}}
            @unless ($task->status->isTerminal())
            <div class="mt-5 pt-5 border-t border-gray-100">
                <div class="flex items-center justify-between mb-3">
                    <span class="text-xs font-semibold uppercase text-gray-400" style="letter-spacing:.1em;">Status — tap to update</span>
                    <span id="ts_pill" class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"></span>
                </div>
                <div id="ts_bar" class="flex border border-gray-200 rounded-xl overflow-hidden bg-white"></div>
                <p id="ts_desc" class="text-sm text-gray-500 mt-3 mb-0"></p>

                {{-- Waiting: a reason is required before the hold commits --}}
                <div id="ts_waiting_panel" class="hidden mt-4 rounded-xl p-5" style="border:1px solid #fde68a; background:#fffbeb;">
                    <div class="flex items-center gap-3 mb-3">
                        <span class="flex items-center justify-center rounded-lg font-bold" style="width:30px;height:30px;background:#fef3c7;color:#b45309;">&#9208;</span>
                        <div>
                            <div class="text-sm font-semibold" style="color:#92400e;">Why is this on hold?</div>
                            <div class="text-xs" style="color:#b45309;">A reason is required before the task can be set to Waiting.</div>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach (['Blocked by another task', 'Awaiting a reply', 'Missing information', 'Vendor / third party'] as $reason)
                            <button type="button" data-ts-wait-chip
                                class="text-xs font-semibold rounded-full px-3 py-1.5 cursor-pointer"
                                style="color:#92400e;background:#fef3c7;border:1px solid #fde68a;">{{ $reason }}</button>
                        @endforeach
                    </div>
                    <label class="block text-xs font-semibold mb-1" style="color:#92400e;">Reason for waiting</label>
                    <textarea id="ts_waiting_reason" rows="2"
                        placeholder="e.g. Waiting on Ashley to confirm the mailbox addresses before I can finish setup."
                        class="w-full rounded-lg px-3 py-2 text-sm text-gray-900 bg-white"
                        style="border:1px solid #fde68a;">{{ $task->status->value === 'waiting' ? $task->waiting_reason : '' }}</textarea>
                    <div class="flex justify-end mt-3">
                        <button type="button" id="ts_waiting_save"
                            class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white cursor-pointer"
                            style="background:#b45309;border:none;">Set to Waiting</button>
                    </div>
                </div>

                {{-- Help Needed: creates a linked task for a teammate --}}
                <div id="ts_help_panel" class="hidden mt-4 rounded-xl p-5" style="border:1px solid #e9d5ff; background:#faf7ff;">
                    <div class="flex items-center gap-3 mb-4">
                        <span class="flex items-center justify-center rounded-lg font-bold" style="width:30px;height:30px;background:#ede9fe;color:#7c3aed;">&#8644;</span>
                        <div>
                            <div class="text-sm font-semibold" style="color:#4c1d95;">Request help — creates a linked task</div>
                            <div class="text-xs" style="color:#8b5cf6;">The person you pick gets a task with your request.</div>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:#6b21a8;">Assign to</label>
                            <select id="ts_help_assignee"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white">
                                <option value="">Choose teammate…</option>
                                @foreach ($users as $helpUser)
                                    <option value="{{ $helpUser->id }}">{{ $helpUser->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold mb-1" style="color:#6b21a8;">Needed by</label>
                            <input type="text" id="ts_help_due" readonly placeholder="Select date &amp; time"
                                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white cursor-pointer">
                        </div>
                    </div>
                    <label class="block text-xs font-semibold mb-1" style="color:#6b21a8;">What do you need from them?</label>
                    <textarea id="ts_help_description" rows="2"
                        placeholder="e.g. Please send me the logins for Ashley & Amber's mailboxes so I can finish the setup."
                        class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm text-gray-900 bg-white"></textarea>
                    <div class="flex justify-end mt-3">
                        <button type="button" id="ts_help_save"
                            class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white cursor-pointer"
                            style="background:#7c3aed;border:none;">Create linked task</button>
                    </div>
                </div>
            </div>
            @endunless
        </div>

        {{-- Comments --}}
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h4 class="text-sm font-semibold text-gray-700 mb-4">Comments ({{ $task->comments->count() }})</h4>

            <div class="space-y-4 mb-5">
                @forelse ($task->comments as $comment)
                    @php
                        $author   = $comment->displayAuthor();
                        $isSynced = $comment->isSynchronized();
                        // Attachments render from the canonical source (child) comment on a
                        // projection; files are never copied. Falls back gracefully to nothing
                        // if the source comment/media was removed.
                        $shownMedia = $isSynced ? optional($comment->sourceComment)->media : $comment->media;
                        // Operational label + linked-task reference for a synchronized projection.
                        $syncTaskId = $comment->sourceTask?->id ?? $comment->source_task_id;
                        $syncPrefix = match ($comment->comment_type) {
                            \App\Enums\Tasks\TaskCommentType::Completed => 'Help Request Completed · ',
                            \App\Enums\Tasks\TaskCommentType::Waiting   => 'Waiting · ',
                            default                                     => 'Help Needed ',
                        };
                    @endphp
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $isSynced ? 'bg-purple-100 text-purple-700' : 'bg-brand-100 text-brand-700' }}">
                            {{ strtoupper(substr($author?->first_name ?? '?', 0, 1)) }}
                        </div>
                        <div class="flex-1 {{ $isSynced ? 'rounded-lg border border-purple-100 bg-purple-50/40 p-3' : '' }}">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-800">{{ $author?->full_name ?? 'Unknown' }}</span>
                                @if (!$isSynced && $comment->comment_type?->isContextual())
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $comment->comment_type->badgeClasses() }}">
                                        {{ $comment->comment_type->label() }}
                                    </span>
                                @endif
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>

                            {{-- Synchronized projection: who sent it, which delegated task,
                                 and a link to open that task. --}}
                            @if ($isSynced)
                                <div class="text-xs font-semibold mb-1.5" style="color:#7c3aed;">
                                    {{ $syncPrefix }}@if ($comment->sourceTask)<a href="{{ route('admin.tasks.show', $comment->sourceTask) }}" class="underline hover:no-underline">Task #{{ $syncTaskId }}</a>@else<span>Task #{{ $syncTaskId }}</span>@endif
                                </div>
                            @endif

                            @if ($isSynced && trim((string) $comment->comment) === '' && $comment->comment_type === \App\Enums\Tasks\TaskCommentType::Completed)
                                <p class="text-sm text-gray-700">{{ $author?->full_name ?? 'The assignee' }} completed Help Needed Task #{{ $syncTaskId }}.</p>
                            @else
                                <p class="text-sm text-gray-700 whitespace-pre-wrap">{{ $comment->comment }}</p>
                            @endif

                            @if ($shownMedia && $shownMedia->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-2">
                                    @foreach ($shownMedia as $media)
                                        @if ($media->isImage())
                                            <a href="{{ $media->url }}" target="_blank" title="{{ $media->original_filename }}">
                                                <img src="{{ $media->url }}" alt="{{ $media->original_filename }}"
                                                    class="h-16 w-16 object-cover rounded-md border border-gray-200 hover:opacity-80 transition">
                                            </a>
                                        @else
                                            <video controls preload="metadata" title="{{ $media->original_filename }}"
                                                class="h-16 rounded-md border border-gray-200 bg-black">
                                                <source src="{{ $media->url }}" type="{{ $media->mime_type }}">
                                            </video>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            {{-- Reply to Linked Task — posts into the child task; the sync
                                 process mirrors it back so the thread stays canonical. --}}
                            @if ($isSynced)
                                <button type="button" onclick="toggleLinkedReply({{ $comment->id }})"
                                    class="mt-2 inline-flex items-center gap-1.5 text-xs font-semibold" style="color:#7c3aed;">
                                    <x-heroicon-o-arrow-uturn-left class="w-3.5 h-3.5" />
                                    Reply to Linked Task
                                </button>
                                <form id="linked-reply-{{ $comment->id }}" class="hidden mt-2"
                                    method="POST" action="{{ route('admin.tasks.comments.reply', [$task, $comment]) }}"
                                    enctype="multipart/form-data">
                                    @csrf
                                    <textarea name="comment" rows="2" required placeholder="Reply to {{ $author?->first_name ?? 'the assignee' }}…"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500"></textarea>
                                    <input type="file" name="media[]" multiple
                                        accept="image/*,video/mp4,video/quicktime,video/x-msvideo,video/webm"
                                        class="mt-2 block w-full text-xs text-gray-700 border border-gray-300 rounded-md cursor-pointer bg-white file:mr-3 file:py-1.5 file:px-3 file:border-0 file:rounded-l-md file:bg-gray-100 file:text-xs file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                                    <div class="flex justify-end gap-2 mt-2">
                                        <button type="button" onclick="toggleLinkedReply({{ $comment->id }})"
                                            class="px-3 py-1.5 text-xs rounded-md border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs rounded-md text-white" style="background:#7c3aed;">Send reply</button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No comments yet.</p>
                @endforelse
            </div>

            <form id="comment-form" method="POST" action="{{ route('admin.tasks.comments.store', $task) }}" enctype="multipart/form-data">
                @csrf
                <textarea id="task-comment" name="comment" rows="3" placeholder="Add a comment..."
                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500 mb-3"></textarea>

                <div class="mb-3">
                    <input type="file" name="media[]" multiple
                        accept="image/*,video/mp4,video/quicktime,video/x-msvideo,video/webm"
                        class="block w-full text-sm text-gray-700 border border-gray-300 rounded-md cursor-pointer bg-white
                               file:mr-3 file:py-2 file:px-3 file:border-0 file:rounded-l-md file:bg-gray-100 file:text-sm file:font-medium file:text-gray-700 hover:file:bg-gray-200">
                    <p class="text-xs text-gray-400 mt-1">Optional images / video — removed 30 days after the task is completed.</p>
                </div>

                <div id="comment-validation-msg" class="hidden mb-2 text-sm text-red-600 font-medium">
                    Please enter a completion note before marking this task complete.
                </div>

                <div class="flex items-center justify-end gap-3">
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
    <div class="w-full xl:w-80 shrink-0 space-y-5">

        {{-- Related To Panel --}}
        @if ($task->customer || $task->supplier || $task->related_other || $task->order)
        <div class="bg-white rounded-lg border border-brand-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <x-heroicon-o-user class="w-4 h-4 text-brand-500" />
                Related To
            </h4>
            <dl class="space-y-2 text-sm">
                @if ($task->customer)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Customer</dt>
                    <dd class="font-semibold text-gray-900 text-right max-w-[180px]">{{ $task->customer->full_name }}</dd>
                </div>
                @if (trim((string) $task->customer->phone))
                <div class="flex justify-between">
                    <dt class="text-gray-500">Phone</dt>
                    <dd class="font-medium text-gray-800">{{ \App\Helpers\CustomHelper::formatPhone($task->customer->phone) }}</dd>
                </div>
                @endif
                @elseif ($task->supplier)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Supplier</dt>
                    <dd class="font-semibold text-gray-900 text-right max-w-[180px]">{{ $task->supplier->name }}</dd>
                </div>
                @if (trim((string) $task->supplier->phone))
                <div class="flex justify-between">
                    <dt class="text-gray-500">Phone</dt>
                    <dd class="font-medium text-gray-800">{{ \App\Helpers\CustomHelper::formatPhone($task->supplier->phone) }}</dd>
                </div>
                @endif
                @elseif ($task->related_other)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Other</dt>
                    <dd class="font-semibold text-gray-900 text-right max-w-[180px]">{{ $task->related_other }}</dd>
                </div>
                @endif
                @if ($task->order)
                <div class="flex justify-between">
                    <dt class="text-gray-500">Order</dt>
                    <dd class="text-right">
                        <a href="{{ route('admin.order-management.orders.edit', $task->order->unique_id) }}"
                            class="font-semibold text-blue-600 hover:underline">{{ $task->order->order_number }}</a>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
        @endif

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
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-sm font-semibold text-gray-700">Details</h4>
                <button type="button" onclick="openTaskReassignModal()"
                    class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-1 text-xs font-medium text-gray-600 hover:bg-gray-50">
                    Reassign
                </button>
            </div>
            <dl class="space-y-3 text-sm">
                <div class="flex justify-between">
                    <dt class="text-gray-500">Assigned To</dt>
                    <dd id="task-assignee-display" class="font-medium text-gray-800">{{ $task->assignedTo?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created By</dt>
                    <dd class="font-medium text-gray-800">{{ $task->createdBy?->full_name ?? '—' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-gray-500">Created</dt>
                    <dd class="font-medium text-gray-800">{{ $task->created_at->format('M j, Y') }}</dd>
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
            </dl>
        </div>

        {{-- Linked Tasks (Help Needed sub-tasks / parent) --}}
        @if ($task->parentTask || $task->subTasks->isNotEmpty())
        <div class="bg-white rounded-lg border border-purple-200 shadow-sm p-5">
            <h4 class="text-sm font-semibold text-gray-700 mb-4 flex items-center gap-2">
                <x-heroicon-o-link class="w-4 h-4" style="color:#7c3aed;" />
                Linked Tasks
            </h4>
            <div class="space-y-3 text-sm">
                @if ($task->parentTask)
                    <div>
                        <div class="text-xs text-gray-400 mb-0.5">Assists</div>
                        <a href="{{ route('admin.tasks.show', $task->parentTask) }}"
                            class="font-medium text-blue-600 hover:underline">{{ $task->parentTask->title }}</a>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ml-1 {{ $task->parentTask->status->color() }}">
                            {{ $task->parentTask->status->label() }}
                        </span>
                    </div>
                @endif
                @foreach ($task->subTasks as $subTask)
                    <div>
                        <div class="text-xs text-gray-400 mb-0.5">Help request — {{ $subTask->assignedTo?->full_name ?? 'Unassigned' }}</div>
                        <a href="{{ route('admin.tasks.show', $subTask) }}"
                            class="font-medium text-blue-600 hover:underline">{{ $subTask->title }}</a>
                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ml-1 {{ $subTask->status->color() }}">
                            {{ $subTask->status->label() }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
        @endif

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

{{-- Task Reassign Modal --}}
<div id="TaskReassignModal" style="display:none;"
    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50 px-4 hidden">
    <div class="bg-white rounded-lg shadow-xl w-full max-w-sm border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900">Reassign Task</h2>
            <button type="button" onclick="closeTaskReassignModal()" class="text-gray-400 hover:text-gray-700 text-xl">&times;</button>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label>
            <select id="task_reassign_user"
                class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-brand-500 focus:ring-1 focus:ring-brand-500">
                <option value="">Unassigned</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}" {{ $task->assigned_to_user_id == $user->id ? 'selected' : '' }}>{{ $user->full_name }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeTaskReassignModal()"
                class="px-4 py-2 text-sm rounded-lg border border-gray-300 bg-white text-gray-700 hover:bg-gray-50">Cancel</button>
            <button type="button" id="task-reassign-btn" onclick="submitTaskReassign()"
                class="px-4 py-2 text-sm rounded-lg bg-brand-500 text-white hover:bg-brand-600">Confirm</button>
        </div>
    </div>
</div>

@push('js')
@unless ($task->status->isTerminal())
<script>
(function () {
    'use strict';

    // Task Status Bar design — four working states, freely switchable in any
    // direction. Colors mirror the approved design (1a segmented control).
    var STATUSES = [
        { key: 'open',        label: 'Open',        desc: 'Created — no one has acted on it yet.',             color: '#64748b', bg: '#f1f5f9' },
        { key: 'in_progress', label: 'In Progress', desc: 'Someone is actively working on this task.',         color: '#0d9488', bg: '#ccfbf1' },
        { key: 'waiting',     label: 'Waiting',     desc: 'On hold — blocked or awaiting something external.', color: '#b45309', bg: '#fef3c7' },
        { key: 'help_needed', label: 'Help Needed', desc: 'Spins up a linked task so a teammate can assist.',  color: '#7c3aed', bg: '#ede9fe' },
    ];

    var STATUS_URL     = "{{ route('admin.tasks.status', $task) }}";
    var saved          = @json($task->status->value);   // committed status on the server
    var selected       = saved;                          // visual selection (may be pending a panel commit)
    var WAITING_REASON = @json($task->waiting_reason);

    function cfg(key) {
        return STATUSES.find(function (s) { return s.key === key; }) || STATUSES[0];
    }

    function render() {
        var bar = document.getElementById('ts_bar');
        bar.innerHTML = '';
        STATUSES.forEach(function (s, i) {
            var on  = s.key === selected;
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.style.cssText = 'flex:1;display:flex;align-items:center;justify-content:center;gap:7px;'
                + 'padding:13px 6px;cursor:pointer;font-size:13.5px;font-family:inherit;border:none;'
                + 'transition:background .18s ease,color .18s ease;'
                + 'border-right:' + (i < STATUSES.length - 1 ? '1px solid #e2e8f0' : 'none') + ';'
                + 'background:' + (on ? s.color : '#fff') + ';'
                + 'color:' + (on ? '#fff' : '#64748b') + ';'
                + 'font-weight:' + (on ? '600' : '500') + ';';
            var dot = document.createElement('span');
            dot.style.cssText = 'width:8px;height:8px;border-radius:50%;'
                + 'background:' + (on ? '#fff' : s.color) + ';opacity:' + (on ? '1' : '.55') + ';';
            btn.appendChild(dot);
            btn.appendChild(document.createTextNode(s.label));
            if (!on) {
                btn.addEventListener('mouseenter', function () { btn.style.background = '#f8fafc'; btn.style.color = '#334155'; });
                btn.addEventListener('mouseleave', function () { btn.style.background = '#fff';    btn.style.color = '#64748b'; });
            }
            btn.addEventListener('click', function () { pick(s.key); });
            bar.appendChild(btn);
        });

        var current = cfg(selected);
        var pill = document.getElementById('ts_pill');
        pill.textContent = current.label;
        pill.style.background = current.bg;
        pill.style.color = current.color;

        var desc = document.getElementById('ts_desc');
        desc.textContent = (selected === 'waiting' && selected === saved && WAITING_REASON)
            ? 'On hold — ' + WAITING_REASON
            : current.desc;

        document.getElementById('ts_waiting_panel').classList.toggle('hidden', selected !== 'waiting' || selected === saved);
        document.getElementById('ts_help_panel').classList.toggle('hidden', selected !== 'help_needed' || selected === saved);
    }

    function pick(key) {
        selected = key;
        // Open / In Progress commit immediately; Waiting and Help Needed
        // stay pending until their panel is completed.
        if (key === 'open' || key === 'in_progress') {
            if (key === saved) { render(); return; }
            commit({ status: key });
            return;
        }
        render();
    }

    function commit(payload, btn) {
        if (btn) { btn.disabled = true; }
        fetch(STATUS_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify(payload),
        })
        .then(function (res) {
            if (res.status === 422) {
                return res.json().then(function (data) {
                    var first = data.errors ? Object.values(data.errors)[0][0] : (data.message || 'Validation error.');
                    throw new Error(first);
                });
            }
            if (!res.ok) throw new Error('Server error (' + res.status + ').');
            return res.json();
        })
        .then(function (data) {
            if (!data.success) throw new Error(data.message || 'Could not update status.');
            notyf.success(data.message);
            window.location.reload();
        })
        .catch(function (err) {
            notyf.error(err.message);
            selected = saved;
            render();
        })
        .finally(function () { if (btn) { btn.disabled = false; } });
    }

    document.getElementById('ts_waiting_save').addEventListener('click', function () {
        var reason = document.getElementById('ts_waiting_reason').value.trim();
        if (!reason) { notyf.error('Please enter a reason for waiting.'); return; }
        commit({ status: 'waiting', waiting_reason: reason }, this);
    });

    document.getElementById('ts_help_save').addEventListener('click', function () {
        var assignee    = document.getElementById('ts_help_assignee').value;
        var description = document.getElementById('ts_help_description').value.trim();
        if (!assignee)    { notyf.error('Please choose a teammate to help.'); return; }
        if (!description) { notyf.error('Please describe what you need from them.'); return; }
        commit({
            status:           'help_needed',
            help_assigned_to: assignee,
            help_description: description,
            help_due_date:    document.getElementById('ts_help_due').value || null,
        }, this);
    });

    // Quick-pick reason chips fill the waiting textarea
    document.querySelectorAll('[data-ts-wait-chip]').forEach(function (chip) {
        chip.addEventListener('click', function () {
            document.getElementById('ts_waiting_reason').value = chip.textContent.trim();
            document.getElementById('ts_waiting_reason').focus();
        });
    });

    // ── Needed By: canonical Flatpickr (same lazy-load + options as the New
    //    Task modal). Init once and reuse — reopening the Help panel never
    //    creates a second instance; the value survives a validation error
    //    because that path shows an inline error without reloading. ──────────
    var _tsHelpPicker = null;
    function initHelpDuePicker() {
        if (_tsHelpPicker || !window.flatpickr) return;
        _tsHelpPicker = flatpickr('#ts_help_due', {
            enableTime:    true,
            dateFormat:    'Y-m-d H:i:S',
            altInput:      true,
            altFormat:     'F j, Y h:i K',
            minDate:       'today',
            time_24hr:     false,
            disableMobile: true,
            onReady: function (sel, str, instance) {
                instance.calendarContainer.style.zIndex = '200000';
            },
        });
    }
    if (window.flatpickr) {
        initHelpDuePicker();
    } else {
        if (!document.getElementById('flatpickr-css')) {
            var fpCss = document.createElement('link');
            fpCss.id = 'flatpickr-css'; fpCss.rel = 'stylesheet';
            fpCss.href = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css';
            document.head.appendChild(fpCss);
        }
        var fpJs = document.createElement('script');
        fpJs.src = 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.js';
        fpJs.onload = initHelpDuePicker;
        document.head.appendChild(fpJs);
    }

    render();
}());
</script>
@endunless
<script>
// Reply to Linked Task — reveal the inline reply editor under a synced comment
function toggleLinkedReply(id) {
    var f = document.getElementById('linked-reply-' + id);
    if (!f) return;
    f.classList.toggle('hidden');
    if (!f.classList.contains('hidden')) {
        var ta = f.querySelector('textarea');
        if (ta) ta.focus();
    }
}
</script>
<script>
function openTaskReassignModal() {
    var m = document.getElementById('TaskReassignModal');
    m.style.display = 'flex'; m.classList.remove('hidden');
}
function closeTaskReassignModal() {
    var m = document.getElementById('TaskReassignModal');
    m.style.display = 'none'; m.classList.add('hidden');
}
function submitTaskReassign() {
    var btn = document.getElementById('task-reassign-btn');
    btn.disabled = true; btn.textContent = 'Saving...';
    fetch("{{ route('admin.tasks.reassign', $task) }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({ assigned_to_user_id: document.getElementById('task_reassign_user').value || null }),
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (!data.success) throw new Error(data.message || 'Error');
        notyf.success(data.message);
        document.getElementById('task-assignee-display').textContent = data.assignee;
        closeTaskReassignModal();
    })
    .catch(function (err) { notyf.error(err.message); })
    .finally(function () { btn.disabled = false; btn.textContent = 'Confirm'; });
}
document.getElementById('TaskReassignModal').addEventListener('click', function (e) {
    if (e.target === this) closeTaskReassignModal();
});
</script>
@endpush

@endsection
