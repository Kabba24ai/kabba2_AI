<?php

namespace App\Http\Controllers\Admin\Tasks;

use App\Enums\Tasks\TaskCategory;
use App\Enums\Tasks\TaskPriority;
use App\Enums\Tasks\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tasks\StoreTaskCommentRequest;
use App\Http\Requests\Admin\Tasks\StoreTaskRequest;
use App\Http\Requests\Admin\Tasks\UpdateTaskRequest;
use App\Models\Iam\Personnel\User;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Tasks\Task;
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

        $equipmentList = Equipment::with('productCategory:id,title')
            ->whereNotNull('product_category_id')
            ->orderBy('equipment_name')
            ->get(['id', 'equipment_id', 'equipment_name', 'product_category_id', 'current_status', 'serial_number']);

        return compact('productCategories', 'equipmentList');
    }

    public function index(Request $request)
    {
        $query = Task::with(['assignedTo', 'createdBy', 'equipment'])
            ->when($request->filled('category'), fn($q) => $q->where('category', $request->category))
            ->when($request->filled('status'),   fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'), fn($q) => $q->where('priority', $request->priority))
            ->when($request->filled('assigned_to'), fn($q) => $q->where('assigned_to_user_id', $request->assigned_to))
            ->when($request->boolean('due_today'),  fn($q) => $q->dueToday())
            ->when($request->boolean('overdue'),    fn($q) => $q->overdue());

        $tasks = $query
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

        $users      = User::active()->orderBy('first_name')->get();
        $categories = TaskCategory::cases();
        $priorities = TaskPriority::cases();
        $statuses   = TaskStatus::cases();

        return view('admin.tasks.index', compact('tasks', 'users', 'categories', 'priorities', 'statuses'));
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
        $task->load(['assignedTo', 'createdBy', 'equipment.productCategory', 'comments.user', 'activityLogs.user']);

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
}
