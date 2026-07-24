<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;
use Illuminate\Http\Request;

class IndexController extends BaseController
{
    public function __invoke(Request $request)
    {
        $user = auth('api_user')->user();

        $tasks = Task::with(['assignedTo', 'createdBy', 'equipment.productCategory'])
            ->when($request->filled('category'),    fn($q) => $q->where('category', $request->category))
            ->when($request->filled('status'),      fn($q) => $q->where('status', $request->status))
            ->when($request->filled('priority'),    fn($q) => $q->where('priority', $request->priority))
            ->when($request->boolean('assigned_to_me'), fn($q) => $q->where('assigned_to_user_id', $user->id))
            ->when($request->boolean('due_today'),  fn($q) => $q->dueToday())
            ->when($request->boolean('overdue'),    fn($q) => $q->overdue())
            ->orderByRaw("CASE WHEN `due_date` IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_date')
            ->orderByRaw("CASE `priority` WHEN 'urgent' THEN 1 WHEN 'high' THEN 2 WHEN 'normal' THEN 3 WHEN 'low' THEN 4 ELSE 5 END")
            ->paginate(30);

        return response()->json([
            'success' => true,
            'data'    => $tasks->map(fn($t) => $this->format($t)),
            'meta'    => [
                'current_page' => $tasks->currentPage(),
                'last_page'    => $tasks->lastPage(),
                'total'        => $tasks->total(),
            ],
        ]);
    }

    private function format(Task $task): array
    {
        return [
            'id'           => $task->id,
            'category'     => $task->category->value,
            'category_label' => $task->category->label(),
            'title'        => $task->title,
            'description'  => $task->description,
            'priority'     => $task->priority->value,
            'priority_label' => $task->priority->label(),
            'status'       => $task->status->value,
            'status_label' => $task->status->label(),
            'assigned_to'  => $task->assignedTo ? ['id' => $task->assignedTo->id, 'name' => $task->assignedTo->full_name] : null,
            'created_by'   => $task->createdBy  ? ['id' => $task->createdBy->id,  'name' => $task->createdBy->full_name]  : null,
            'equipment'    => $task->equipment ? [
                'id'           => $task->equipment->id,
                'equipment_id' => $task->equipment->equipment_id,
                'name'         => $task->equipment->equipment_name,
                'category'     => $task->equipment->productCategory?->title,
                'serial'       => $task->equipment->serial_number,
                'status'       => $task->equipment->status_label,
                'power_source_type' => $task->equipment->power_source_type,
                'key_starting_mechanism' => $task->equipment->key_starting_mechanism,
                'is_fuel'      => $task->equipment->hasFuelData(),
                'is_key'       => $task->equipment->hasKeyData(),
            ] : null,
            'due_date'     => $task->due_date?->toDateTimeString(),
            'completed_at' => $task->completed_at?->toDateTimeString(),
            'is_overdue'   => $task->isOverdue(),
            'created_at'   => $task->created_at->toDateTimeString(),
        ];
    }
}
