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

        // User badge counts: base filters + category (cross-filter: excludes assigned_to)
        $userCountsRaw = $this->baseTaskQuery($request)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->whereNotNull('assigned_to_user_id')
            ->selectRaw('assigned_to_user_id, count(*) as cnt')
            ->groupBy('assigned_to_user_id')
            ->pluck('cnt', 'assigned_to_user_id');

        // Total for "All" user badge (base + category, including unassigned tasks)
        $userAllCount = $this->baseTaskQuery($request)
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->count();

        // User names for badge labels
        $badgeUsers = User::whereIn('id', $userCountsRaw->keys())
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        // Full filtered task list
        $tasks = $this->baseTaskQuery($request)
            ->with(['assignedTo', 'createdBy', 'equipment'])
            ->when($request->filled('category'),    fn($q) => $q->where('category', $request->category))
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
            ->orderByRaw("CASE `status`
                WHEN 'open'        THEN 1
                WHEN 'in_progress' THEN 2
                WHEN 'waiting'     THEN 3
                WHEN 'completed'   THEN 4
                WHEN 'cancelled'   THEN 5
                ELSE 6 END")
            ->orderByRaw("CASE `priority`
                WHEN 'urgent' THEN 1
                WHEN 'high'   THEN 2
                WHEN 'normal' THEN 3
                WHEN 'low'    THEN 4
                ELSE 5 END")
            ->orderBy('due_date')
            ->paginate(30)
            ->withQueryString();

        $users = User::active()->orderBy('first_name')->get();

        $completedToday = Task::completedToday()
            ->with(['assignedTo'])
            ->orderByDesc('completed_at')
            ->get();

        $customers = Customer::whereIn('status', ['Active', 'Archived'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $suppliers = Supplier::active()->orderBy('name')->get(['id', 'name', 'phone', 'email', 'primary_contact_name', 'primary_contact_phone']);

        $callReminders = CustomerCallNeeded::with(['customer', 'supplier', 'assignee', 'creator'])
            ->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('follow_up_at')->orWhere('follow_up_at', '<=', now());
            })
            ->latest()
            ->get();

        return view('admin.tasks.index', compact(
            'tasks', 'users', 'categories', 'priorities', 'statuses',
            'categoryCounts', 'userCountsRaw', 'userAllCount', 'badgeUsers',
            'completedToday', 'customers', 'suppliers', 'callReminders'
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

        return redirect()->route('admin.tasks.show', $task)->with('success', 'Task created successfully.');
    }

    public function show(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'completedBy', 'equipment.productCategory', 'comments.user', 'activityLogs.user']);

        return view('admin.tasks.show', compact('task'));
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

    public function archive(Request $request)
    {
        $query = Task::with(['assignedTo', 'createdBy', 'completedBy', 'equipment'])
            ->where('status', 'completed');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(fn($q) => $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to_user_id', $request->assigned_to);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('completed_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('completed_at', '<=', $request->date_to);
        }

        $tasks = $query->orderByDesc('completed_at')->paginate(30)->withQueryString();

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
