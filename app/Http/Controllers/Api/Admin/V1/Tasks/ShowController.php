<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;

class ShowController extends BaseController
{
    public function __invoke(Task $task)
    {
        $task->load(['assignedTo', 'createdBy', 'equipment.productCategory', 'comments.user', 'activityLogs.user']);

        return response()->json([
            'success' => true,
            'data'    => [
                'id'             => $task->id,
                'category'       => $task->category->value,
                'category_label' => $task->category->label(),
                'title'          => $task->title,
                'description'    => $task->description,
                'priority'       => $task->priority->value,
                'priority_label' => $task->priority->label(),
                'status'         => $task->status->value,
                'status_label'   => $task->status->label(),
                'assigned_to'    => $task->assignedTo ? ['id' => $task->assignedTo->id, 'name' => $task->assignedTo->full_name] : null,
                'created_by'     => $task->createdBy  ? ['id' => $task->createdBy->id,  'name' => $task->createdBy->full_name]  : null,
                'equipment'      => $task->equipment ? [
                    'id'           => $task->equipment->id,
                    'equipment_id' => $task->equipment->equipment_id,
                    'name'         => $task->equipment->equipment_name,
                    'category'     => $task->equipment->productCategory?->title,
                    'serial'       => $task->equipment->serial_number,
                    'status'       => $task->equipment->status_label,
                ] : null,
                'due_date'       => $task->due_date?->toDateTimeString(),
                'completed_at'   => $task->completed_at?->toDateTimeString(),
                'is_overdue'     => $task->isOverdue(),
                'comments'       => $task->comments->map(fn($c) => [
                    'id'         => $c->id,
                    'comment'    => $c->comment,
                    'user'       => ['id' => $c->user->id, 'name' => $c->user->full_name],
                    'created_at' => $c->created_at->toDateTimeString(),
                ]),
                'activity_logs'  => $task->activityLogs->map(fn($l) => [
                    'action'     => $l->action,
                    'old_value'  => $l->old_value,
                    'new_value'  => $l->new_value,
                    'user'       => $l->user ? ['id' => $l->user->id, 'name' => $l->user->full_name] : null,
                    'created_at' => $l->created_at->toDateTimeString(),
                ]),
                'created_at'     => $task->created_at->toDateTimeString(),
            ],
        ]);
    }
}
