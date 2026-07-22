<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tasks\StoreTaskCommentRequest;
use App\Http\Requests\Admin\Tasks\StoreTaskRequest;
use App\Http\Requests\Admin\Tasks\UpdateTaskRequest;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function equipmentData(): array
    {
        return \App\Support\Tasks\UnifiedTaskModalData::equipmentData();
    }

    public function index(Request $request)
    {
        $categories = TaskCategory::cases();
        $priorities = TaskPriority::cases();
        $statuses   = TaskStatus::cases();

        // Category badge counts: base filters + assigned_to (cross-filter: excludes category)
        $categoryCounts = $this->baseTaskQuery($request)
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
            ->selectRaw('category, count(*) as cnt')
            ->groupBy('category')
            ->pluck('cnt', 'category');

        // Type filter — determines which item types to include in the unified list
        $typeParam    = $request->input('type', 'all');
        $includeTasks = $typeParam !== 'calls';
        $includeCalls = $typeParam !== 'tasks';

        // Tab badge counts — independent of the type filter so each tab always shows its real total
        $taskCount = $this->baseTaskQuery($request)
            ->when($request->filled('category'),    fn($q) => $q->where('category', $request->category))
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
            ->count();

        $callCount = $this->baseCallQuery($request)
            ->when($request->filled('assigned_to'), fn($q) => $q->where('created_by', $request->assigned_to))
            ->count();

        // Load the relevant model sets (skip a type when its tab is the active filter)
        $taskModels = $includeTasks
            ? $this->baseTaskQuery($request)
                ->with(['assignedTo', 'createdBy', 'equipment', 'customer', 'order'])
                ->when($request->filled('category'),    fn($q) => $q->where('category', $request->category))
                ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
                ->get()
            : collect();

        $callModels = $includeCalls
            ? $this->baseCallQuery($request)
                ->with(['customer', 'supplier', 'assignee', 'creator', 'order'])
                ->when($request->filled('assigned_to'), fn($q) => $q->where('created_by', $request->assigned_to))
                ->get()
            : collect();

        // Merge both types into one flat list, then sort: due_date ASC (nulls last), then priority
        $priorityRank = ['urgent' => 1, 'high' => 2, 'normal' => 3, 'low' => 4];

        $unified = $taskModels
            ->map(fn($t) => (object)[
                'type'          => 'task',
                'model'         => $t,
                'due_ts'        => $t->due_date?->timestamp,
                'priority_rank' => $priorityRank[$t->priority->value] ?? 5,
            ])
            ->concat($callModels->map(fn($c) => (object)[
                'type'          => 'call',
                'model'         => $c,
                'due_ts'        => $c->due_date?->timestamp,
                'priority_rank' => $priorityRank[$c->priority->value] ?? 5,
            ]))
            ->sort(function ($a, $b) {
                // Null due dates always go last
                if (($a->due_ts === null) !== ($b->due_ts === null)) {
                    return $a->due_ts === null ? 1 : -1;
                }
                // Primary: due date ascending
                $dateCmp = ($a->due_ts ?? PHP_INT_MAX) <=> ($b->due_ts ?? PHP_INT_MAX);
                if ($dateCmp !== 0) return $dateCmp;
                // Secondary: priority (Urgent → High → Normal → Low)
                return $a->priority_rank <=> $b->priority_rank;
            })
            ->values();

        // Group the already-sorted stream into per-person lanes: person is the
        // first-level sort so each teammate sees only their own work; due-date
        // then priority ordering is preserved within every lane.
        $lanes = $this->buildPersonLanes($unified);

        $users = User::active()->orderBy('first_name')->get();

        ['productCategories' => $productCategories, 'equipmentList' => $equipmentList] = $this->equipmentData();

        $customers = \App\Support\Tasks\UnifiedTaskModalData::customers();

        $suppliers = \App\Support\Tasks\UnifiedTaskModalData::suppliers();

        return view('admin.tasks.index', compact(
            'lanes', 'users', 'categories', 'priorities', 'statuses',
            'categoryCounts', 'customers', 'suppliers',
            'taskCount', 'callCount', 'productCategories', 'equipmentList'
        ));
    }

    /**
     * Group the merged (already due/priority-sorted) task+call stream into
     * one lane per assignee. Tasks use assigned_to_user_id; calls use their
     * created_by assignee. Unassigned work collects in a trailing lane. Each
     * lane carries a cycling colour and a role line derived from the distinct
     * categories present, mirroring the Task Center "grouped by person" design.
     */
    private function buildPersonLanes(\Illuminate\Support\Collection $unified): array
    {
        $groups = [];
        foreach ($unified as $item) {
            $user = $item->type === 'call' ? $item->model->assignee : $item->model->assignedTo;
            $key  = $user?->id ?? 'unassigned';

            if (!isset($groups[$key])) {
                $groups[$key] = ['user' => $user, 'items' => []];
            }
            $groups[$key]['items'][] = $item;
        }

        // Assigned lanes first (by name), unassigned always trailing.
        uasort($groups, function ($a, $b) {
            if (($a['user'] === null) !== ($b['user'] === null)) {
                return $a['user'] === null ? 1 : -1;
            }
            return strcasecmp((string) $a['user']?->full_name, (string) $b['user']?->full_name);
        });

        $lanes = [];
        foreach ($groups as $key => $group) {
            $user = $group['user'];
            // Canonical, id-keyed theme — identical in summary and focused views,
            // stable across filtering/ordering (never loop-position based).
            $theme = \App\Support\Tasks\EmployeeTheme::for($user?->id);

            $categoryLabels = collect($group['items'])
                ->map(fn ($it) => $it->model->category?->label())
                ->filter()->unique()->values()->all();

            $name     = $user?->full_name ?: 'Unassigned';
            $initials = $user
                ? strtoupper(mb_substr((string) $user->first_name, 0, 1) . mb_substr((string) $user->last_name, 0, 1))
                : '—';

            $lanes[] = [
                'key'      => (string) $key,
                'user_id'  => $user?->id,
                'name'     => $name,
                'initials' => $initials !== '' ? $initials : '—',
                'role'     => implode(' · ', $categoryLabels),
                'color'    => $theme['color'],
                'bg'       => $theme['bg'],
                'items'    => $group['items'],
                'count'    => count($group['items']),
            ];
        }

        return $lanes;
    }

    private function baseTaskQuery(Request $request): Builder
    {
        return Task::query()
            ->when(
                $request->filled('status'),
                fn($q) => $q->where('status', $request->status),
                fn($q) => $q->whereNotIn('status', ['completed', 'cancelled'])
            )
            ->when($request->filled('priority'),    fn($q) => $q->where('priority', $request->priority))
            ->when($request->boolean('due_today'),  fn($q) => $q->dueToday())
            ->when($request->boolean('overdue'),    fn($q) => $q->overdue());
    }

    private function baseCallQuery(Request $request): Builder
    {
        return CustomerCallNeeded::where('status', 'active')
            ->where(fn($q) => $q->whereNull('follow_up_at')->orWhere('follow_up_at', '<=', now()))
            ->when($request->filled('priority'),   fn($q) => $q->where('priority', $request->priority))
            ->when($request->boolean('due_today'), fn($q) => $q->whereDate('due_date', today()))
            ->when($request->boolean('overdue'),   fn($q) => $q->whereNotNull('due_date')->where('due_date', '<', now()));
    }

    public function create()
    {
        $users      = User::active()->orderBy('first_name')->get();
        $categories = TaskCategory::cases();
        $priorities = TaskPriority::cases();
        $statuses   = TaskStatus::cases();
        ['productCategories' => $productCategories, 'equipmentList' => $equipmentList] = $this->equipmentData();

        return view('admin.tasks.create', compact('users', 'categories', 'priorities', 'statuses', 'productCategories', 'equipmentList'));
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->validated();
        $data['created_by_user_id'] = auth()->id();

        if (($data['status'] ?? null) === 'completed' && empty($data['completed_at'])) {
            $data['completed_at'] = now();
        }

        $task = Task::create($data);
        $task->logActivity('task_created', null, $task->title);

        $this->storeTaskMediaFiles($task, null, $request->file('media', []));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Task created successfully.']);
        }

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'completedBy', 'equipment.productCategory', 'customer', 'supplier', 'order', 'parentTask', 'subTasks.assignedTo', 'descriptionMedia', 'comments.user', 'comments.sourceUser', 'comments.sourceTask', 'comments.sourceComment.media', 'comments.media', 'activityLogs.user']);
        $users = User::active()->orderBy('first_name')->get();

        return view('admin.tasks.show', compact('task', 'users'));
    }

    /**
     * Clickable status control on the task detail page. The four working
     * statuses move freely in any direction — this is a current-state
     * selector, not a progress bar. Waiting requires a reason; Help Needed
     * additionally spins up a linked sub-task for the chosen teammate.
     * Completed/Cancelled stay on their existing flows and are never set here.
     */
    public function updateStatus(Request $request, Task $task)
    {
        $request->validate([
            'status'           => 'required|in:open,in_progress,waiting,help_needed',
            'waiting_reason'   => 'required_if:status,waiting|nullable|string|max:500',
            'help_assigned_to' => 'required_if:status,help_needed|nullable|exists:users,id',
            'help_description' => 'required_if:status,help_needed|nullable|string|max:2000',
            'help_due_date'    => 'nullable|date',
        ]);

        if ($task->status->isTerminal()) {
            return response()->json([
                'success' => false,
                'message' => 'This task is ' . strtolower($task->status->label()) . ' — its status can no longer change.',
            ], 422);
        }

        $oldLabel = $task->status->label();

        // The status change, its contextual comment, and any linked task must
        // succeed or fail together — a status must never move while its
        // required comment or sub-task is lost.
        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $task, $oldLabel) {
            $task->update([
                'status'         => $request->status,
                'waiting_reason' => $request->status === 'waiting' ? $request->waiting_reason : null,
            ]);
            $task->refresh();

            // Audit trail records only the transition — the human explanation
            // now lives in Comments, so it is not duplicated here.
            $task->logActivity('status_changed', $oldLabel, $task->status->label());

            // Waiting reason → a conversational comment (canonical create path).
            if ($request->status === 'waiting') {
                $task->comments()->create([
                    'user_id'      => auth()->id(),
                    'comment'      => $request->waiting_reason,
                    'comment_type' => \App\Enums\Tasks\TaskCommentType::Waiting,
                ]);
            }

            // Help Needed → linked sub-task (audit) + help request (comment).
            if ($request->status === 'help_needed') {
                $helpTask = Task::create([
                    'category'             => $task->category->value,
                    'title'                => 'Help needed: ' . $task->title,
                    'description'          => $request->help_description,
                    'priority'             => $task->priority->value,
                    'status'               => 'open',
                    'assigned_to_user_id'  => $request->help_assigned_to,
                    'created_by_user_id'   => auth()->id(),
                    'due_date'             => $request->help_due_date,
                    'parent_task_id'       => $task->id,
                    // The helper inherits the full relationship context
                    'related_customer_id'  => $task->related_customer_id,
                    'related_order_id'     => $task->related_order_id,
                    'related_supplier_id'  => $task->related_supplier_id,
                    'related_other'        => $task->related_other,
                    'related_equipment_id' => $task->related_equipment_id,
                ]);
                $helpTask->logActivity('task_created', null, $helpTask->title);

                // Audit fact only — the request text lives in the comment below.
                $task->logActivity(
                    'help_requested',
                    null,
                    'Linked task created for ' . ($helpTask->assignedTo?->full_name ?? 'Unassigned')
                );

                $task->comments()->create([
                    'user_id'      => auth()->id(),
                    'comment'      => $request->help_description,
                    'comment_type' => \App\Enums\Tasks\TaskCommentType::HelpNeeded,
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Status updated to ' . $task->status->label() . '.',
            'status'  => $task->status->value,
        ]);
    }

    public function reassign(Request $request, Task $task)
    {
        $request->validate(['assigned_to_user_id' => 'nullable|exists:users,id']);

        $oldAssignee = $task->assignedTo?->full_name ?? 'Unassigned';
        $task->update(['assigned_to_user_id' => $request->assigned_to_user_id ?: null]);
        $task->refresh();
        $newAssignee = $task->assignedTo?->full_name ?? 'Unassigned';
        $task->logActivity('reassigned', $oldAssignee, $newAssignee);

        return response()->json(['success' => true, 'message' => 'Task reassigned to ' . $newAssignee . '.', 'assignee' => $newAssignee]);
    }

    public function reassignCall(Request $request, int $id)
    {
        $request->validate(['assigned_to_user_id' => 'nullable|exists:users,id']);

        $call = CustomerCallNeeded::findOrFail($id);
        $call->update(['created_by' => $request->assigned_to_user_id ?: null]);
        $call->refresh();
        $assigneeName = $call->assignee?->full_name ?? 'Unassigned';

        return response()->json(['success' => true, 'message' => 'Call reassigned to ' . $assigneeName . '.', 'assignee' => $assigneeName]);
    }

    public function storeCallNote(Request $request, int $id)
    {
        $request->validate(['notes' => 'required|string|max:2000']);

        $call = CustomerCallNeeded::findOrFail($id);

        \App\Models\Customers\CustomerCallNeededActivity::create([
            'customer_call_needed_id' => $call->id,
            'status'                  => 'note',
            'notes'                   => $request->notes,
            'follow_up_date'          => now(),
            'created_by'              => auth()->id(),
        ]);

        return response()->json(['success' => true, 'message' => 'Note added to call history.']);
    }

    public function destroyCall(int $id)
    {
        $call = CustomerCallNeeded::findOrFail($id);
        $call->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'Call reminder deleted.');
    }

    public function edit(Task $task)
    {
        $users      = User::activeOrIds([$task->assigned_to_user_id])->orderBy('first_name')->get();
        $categories = TaskCategory::cases();
        $priorities = TaskPriority::cases();
        $statuses   = TaskStatus::cases();
        ['productCategories' => $productCategories, 'equipmentList' => $equipmentList] = $this->equipmentData();

        return view('admin.tasks.edit', compact('task', 'users', 'categories', 'priorities', 'statuses', 'productCategories', 'equipmentList'));
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();

        // Track changes for activity log
        $statusChanged   = isset($data['status'])   && $data['status']   !== $task->status->value;
        $priorityChanged = isset($data['priority'])  && $data['priority']  !== $task->priority->value;
        $assigneeChanged = array_key_exists('assigned_to_user_id', $data)
            && $data['assigned_to_user_id'] != $task->assigned_to_user_id;

        $oldStatus   = $task->status->label();
        $oldPriority = $task->priority->label();
        $oldAssignee = $task->assignedTo?->full_name ?? 'Unassigned';

        // Set completed_at when status moves to completed
        if ($statusChanged && $data['status'] === 'completed') {
            $data['completed_at'] = now();
        } elseif ($statusChanged && $task->status->value === 'completed') {
            $data['completed_at'] = null;
        }

        $task->update($data);
        $task->refresh();

        if ($statusChanged) {
            $task->logActivity('status_changed', $oldStatus, $task->status->label());
        }
        if ($priorityChanged) {
            $task->logActivity('priority_changed', $oldPriority, $task->priority->label());
        }
        if ($assigneeChanged) {
            $newAssignee = $task->assignedTo?->full_name ?? 'Unassigned';
            $task->logActivity('reassigned', $oldAssignee, $newAssignee);
        }

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task updated successfully.');
    }

    public function destroy(Task $task)
    {
        $task->delete();

        return redirect()->route('admin.tasks.index')->with('success', 'Task deleted.');
    }

    public function storeComment(StoreTaskCommentRequest $request, Task $task)
    {
        $comment = $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated('comment'),
        ]);

        $this->storeTaskMediaFiles($task, $comment->id, $request->file('media', []));

        $task->logActivity('comment_added');

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Comment added.');
    }

    public function completeWithComment(StoreTaskCommentRequest $request, Task $task)
    {
        $comment = $request->validated('comment');

        // Typed Completed so a child task's completion note mirrors up to the
        // parent as a "Completed" event (see TaskComment sync). Non-child tasks
        // simply get a completion-labelled note on their own timeline.
        $commentModel = $task->comments()->create([
            'user_id'      => auth()->id(),
            'comment'      => $comment,
            'comment_type' => \App\Enums\Tasks\TaskCommentType::Completed,
        ]);

        $this->storeTaskMediaFiles($task, $commentModel->id, $request->file('media', []));

        $task->update([
            'status'               => 'completed',
            'completed_at'         => now(),
            'completed_by_user_id' => auth()->id(),
        ]);

        $task->logActivity(
            'task_completed',
            null,
            'Task marked completed by ' . auth()->user()->full_name . '. Completion Note: ' . $comment
        );

        return redirect()->route('admin.tasks.index')->with('success', 'Task marked as completed.');
    }

    /**
     * Reply to Linked Task — the preferred way the parent's owner answers a
     * synchronized Help Needed comment. The reply is posted as a NATIVE comment
     * on the child (canonical) task; the TaskComment sync then mirrors it back
     * to this parent exactly once. We never write a parent comment directly, so
     * there is only ever one conversation of record.
     */
    public function replyToLinkedComment(StoreTaskCommentRequest $request, Task $task, \App\Models\Tasks\TaskComment $comment)
    {
        abort_unless($comment->task_id === $task->id && $comment->source_task_id !== null, 404);

        $childTask = Task::find($comment->source_task_id);
        abort_unless($childTask !== null, 404);

        \Illuminate\Support\Facades\DB::transaction(function () use ($request, $childTask) {
            $reply = $childTask->comments()->create([
                'user_id' => auth()->id(),
                'comment' => $request->validated('comment'),
            ]);
            // Native child comment → the created hook mirrors it up to the parent.
            $this->storeTaskMediaFiles($childTask, $reply->id, $request->file('media', []));
            $childTask->logActivity('comment_added');
        });

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Reply sent to the linked task.');
    }

    /**
     * Persist uploaded images/video on the dedicated task_media disk under
     * {task_id}/ and record one daily_task_media row per file. Comment id
     * null = attached to the task description.
     */
    private function storeTaskMediaFiles(Task $task, ?int $commentId, array $files): void
    {
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }

            $originalName = $file->getClientOriginalName();
            $baseName     = \Illuminate\Support\Str::lower(pathinfo($originalName, PATHINFO_FILENAME));
            $extension    = $file->getClientOriginalExtension();
            $filename     = \Illuminate\Support\Str::random(6) . '-' . preg_replace('/[^a-z0-9\_\-\.]/i', '', $baseName . '.' . $extension);
            $mime         = $file->getMimeType();

            $path = $file->storeAs((string) $task->id, $filename, \App\Models\Tasks\TaskMedia::DISK);

            $task->media()->create([
                'task_comment_id'   => $commentId,
                'media_type'        => str_starts_with((string) $mime, 'video') ? 'video' : 'image',
                'file_path'         => $path,
                'original_filename' => $originalName,
                'mime_type'         => $mime,
                'file_size'         => $file->getSize(),
                'uploaded_by'       => auth()->id(),
            ]);
        }
    }

    private static function callReasonLabels(): array
    {
        return [
            'contract_renewal'       => 'Contract Renewal',
            'delivery_pickup'        => 'Delivery / Pickup',
            'equipment_availability' => 'Equipment Availability',
            'equipment_return'       => 'Equipment Return',
            'general_followup'       => 'General Follow-up',
            'maintenance_request'    => 'Maintenance Request',
            'order_review'           => 'Order Review',
            'payment_followup'       => 'Payment Follow-up',
            'rental_inquiry'         => 'Rental Inquiry',
            'returning_call'         => 'Returning Their Call',
            'availability_lead_time' => 'Availability / Lead Time',
            'equipment_service'      => 'Equipment Service / Technical Support',
            'invoice_billing'        => 'Invoice / Billing Question',
            'order_parts'            => 'Order Parts',
            'order_status'           => 'Order Status',
            'other'                  => 'Other',
            'price_quote'            => 'Price Quote',
            'return_exchange'        => 'Return / Exchange',
            'warranty_defective'     => 'Warranty / Defective Item',
        ];
    }

    public function archive(Request $request)
    {
        $search     = $request->filled('search')      ? $request->search      : null;
        $category   = $request->filled('category')    ? $request->category    : null;
        $assignedTo = $request->filled('assigned_to') ? $request->assigned_to : null;
        $dateFrom   = $request->filled('date_from')   ? $request->date_from   : null;
        $dateTo     = $request->filled('date_to')     ? $request->date_to     : null;

        // Tasks
        $taskQuery = Task::with(['assignedTo', 'createdBy', 'completedBy', 'equipment'])
            ->where('status', 'completed');

        if ($search) {
            $taskQuery->where(fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }
        if ($category)   { $taskQuery->where('category', $category); }
        if ($assignedTo) { $taskQuery->where('assigned_to_user_id', $assignedTo); }
        if ($dateFrom)   { $taskQuery->whereDate('completed_at', '>=', $dateFrom); }
        if ($dateTo)     { $taskQuery->whereDate('completed_at', '<=', $dateTo); }

        // Completed calls
        $callQuery = CustomerCallNeeded::with(['assignee', 'creator', 'completedBy'])
            ->where('status', 'clear')
            ->whereNotNull('completed_at');

        if ($search) {
            $callQuery->where(fn($q) => $q->where('reason', 'like', "%{$search}%")
                ->orWhere('notes', 'like', "%{$search}%"));
        }
        if ($category)   { $callQuery->where('category', $category); }
        if ($assignedTo) { $callQuery->where('created_by', $assignedTo); }
        if ($dateFrom)   { $callQuery->whereDate('completed_at', '>=', $dateFrom); }
        if ($dateTo)     { $callQuery->whereDate('completed_at', '<=', $dateTo); }

        $reasonLabels = self::callReasonLabels();

        $taskItems = $taskQuery->get()->map(fn($t) => (object)[
            'type'              => 'task',
            'completed_at'      => $t->completed_at,
            'category'          => $t->category,
            'title'             => $t->title,
            'assignee_name'     => $t->assignedTo?->full_name,
            'creator_name'      => $t->createdBy?->full_name,
            'completed_by_name' => $t->completedBy?->full_name,
            'equipment'         => $t->equipment,
            'view_url'          => route('admin.tasks.show', $t),
        ]);

        $callItems = $callQuery->get()->map(fn($c) => (object)[
            'type'              => 'call',
            'completed_at'      => $c->completed_at,
            'category'          => $c->category,
            'title'             => $reasonLabels[$c->reason] ?? ucwords(str_replace('_', ' ', $c->reason ?? '')),
            'assignee_name'     => $c->assignee?->full_name,
            'creator_name'      => $c->creator?->full_name,
            'completed_by_name' => $c->completedBy?->full_name,
            'equipment'         => null,
            'view_url'          => route('admin.tasks.call.show', $c->id),
        ]);

        $perPage = 30;
        $page    = (int) $request->input('page', 1);
        $all     = $taskItems->concat($callItems)->sortByDesc(fn($i) => $i->completed_at?->timestamp ?? 0)->values();
        $total   = $all->count();
        $items   = $all->slice(($page - 1) * $perPage, $perPage)->values();

        $tasks = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $categories = TaskCategory::cases();
        $users      = User::active()->orderBy('first_name')->get();

        return view('admin.tasks.archive', compact('tasks', 'categories', 'users'));
    }

    public function showCall(int $id)
    {
        $callReminder = CustomerCallNeeded::with(['customer', 'supplier', 'assignee', 'creator', 'order', 'activities.user'])
            ->findOrFail($id);

        $customers = \App\Support\Tasks\UnifiedTaskModalData::customers();

        $suppliers = \App\Support\Tasks\UnifiedTaskModalData::suppliers();

        $users = User::active()->orderBy('first_name')->get();

        return view('admin.tasks.call_show', compact('callReminder', 'customers', 'suppliers', 'users'));
    }
}
