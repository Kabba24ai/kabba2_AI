<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tasks\StoreTaskCommentRequest;
use App\Http\Requests\Admin\Tasks\StoreTaskRequest;
use App\Http\Requests\Admin\Tasks\UpdateTaskRequest;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Tasks\Task;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    private function equipmentData(): array
    {
        $usedCategoryIds = Equipment::whereNotNull('product_category_id')
            ->pluck('product_category_id')
            ->unique();

        $productCategories = ProductCategory::whereNull('parent_id')
            ->whereIn('id', $usedCategoryIds)
            ->orderBy('title')
            ->get(['id', 'title']);

        $statusLabels = [
            'available'   => 'Available',
            'rented'      => 'Rented',
            'maintenance' => 'Maint. Hold',
            'damaged'     => 'Damaged',
        ];

        $equipmentList = Equipment::whereNotNull('product_category_id')
            ->orderBy('equipment_name')
            ->get(['id', 'equipment_id', 'equipment_name', 'product_category_id', 'current_status', 'serial_number'])
            ->map(fn($e) => [
                'id'          => $e->id,
                'equipment_id' => $e->equipment_id,
                'name'        => $e->equipment_name,
                'category_id' => $e->product_category_id,
                'status'      => $statusLabels[$e->getRawOriginal('current_status')] ?? '',
                'serial'      => $e->serial_number ?? '',
            ]);

        return compact('productCategories', 'equipmentList');
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

        // Task user counts: base filters + category (cross-filter: excludes assigned_to)
        $taskUserCounts = $this->baseTaskQuery($request)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->whereNotNull('assigned_to_user_id')
            ->selectRaw('assigned_to_user_id, count(*) as cnt')
            ->groupBy('assigned_to_user_id')
            ->pluck('cnt', 'assigned_to_user_id');

        // Call reminder counts by assignee (cross-filter: excludes assigned_to)
        $callUserCounts = $this->baseCallQuery($request)
            ->whereNotNull('created_by')
            ->selectRaw('created_by, count(*) as cnt')
            ->groupBy('created_by')
            ->pluck('cnt', 'created_by');

        // Merge task + call counts per user for Assigned To badges
        $allBadgeUserIds = $taskUserCounts->keys()->merge($callUserCounts->keys())->unique();
        $userCountsRaw   = $allBadgeUserIds->mapWithKeys(fn($id) => [
            $id => $taskUserCounts->get($id, 0) + $callUserCounts->get($id, 0),
        ]);

        // Total for "All" user badge: tasks + calls (both applying their base filters)
        $userAllCount = $this->baseTaskQuery($request)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->count()
            + $this->baseCallQuery($request)->count();

        // User names for badge labels (includes call reminder assignees)
        $badgeUsers = User::whereIn('id', $userCountsRaw->keys())
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        // Full filtered task list
        $tasks = $this->baseTaskQuery($request)
            ->with(['assignedTo', 'createdBy', 'equipment'])
            ->when($request->filled('category'),    fn($q) => $q->where('category', $request->category))
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
            ->orderByRaw("CASE WHEN `due_date` IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->orderByRaw("CASE `priority`
                WHEN 'urgent' THEN 1
                WHEN 'high'   THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low'    THEN 4
                ELSE 5 END")
            ->paginate(30)
            ->withQueryString();

        $users = User::active()->orderBy('first_name')->get();

        ['productCategories' => $productCategories, 'equipmentList' => $equipmentList] = $this->equipmentData();

        $completedToday = Task::completedToday()
            ->with(['assignedTo'])
            ->orderByDesc('completed_at')
            ->get();

        $callsCompletedToday = CustomerCallNeeded::where('status', 'clear')
            ->whereDate('completed_at', today())
            ->with(['assignee'])
            ->orderByDesc('completed_at')
            ->get();

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        $callReminders = $this->baseCallQuery($request)
            ->with(['customer', 'supplier', 'assignee', 'creator'])
            ->when($request->filled('assigned_to'), fn($q) => $q->where('created_by', $request->assigned_to))
            ->orderByRaw("CASE WHEN `due_date` IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->orderByRaw("CASE `priority`
                WHEN 'urgent' THEN 1
                WHEN 'high'   THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low'    THEN 4
                ELSE 5 END")
            ->get();

        return view('admin.tasks.index', compact(
            'tasks', 'users', 'categories', 'priorities', 'statuses',
            'categoryCounts', 'userCountsRaw', 'userAllCount', 'badgeUsers',
            'completedToday', 'callsCompletedToday', 'customers', 'suppliers', 'callReminders',
            'productCategories', 'equipmentList'
        ));
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
            ->when($request->boolean('overdue'),   fn($q) => $q->whereNotNull('due_date')->whereDate('due_date', '<', today()));
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

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Task created successfully.']);
        }

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'completedBy', 'equipment.productCategory', 'comments.user', 'activityLogs.user']);
        $users = User::active()->orderBy('first_name')->get();

        return view('admin.tasks.show', compact('task', 'users'));
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
        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $request->validated('comment'),
        ]);

        $task->logActivity('comment_added');

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Comment added.');
    }

    public function completeWithComment(StoreTaskCommentRequest $request, Task $task)
    {
        $comment = $request->validated('comment');

        $task->comments()->create([
            'user_id' => auth()->id(),
            'comment' => $comment,
        ]);

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
        $callReminder = CustomerCallNeeded::with(['customer', 'supplier', 'assignee', 'creator', 'activities.user'])
            ->findOrFail($id);

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        $users = User::active()->orderBy('first_name')->get();

        return view('admin.tasks.call_show', compact('callReminder', 'customers', 'suppliers', 'users'));
    }
}
