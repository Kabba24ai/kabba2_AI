<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Admin\Tasks\UpdateTaskRequest;
use App\Models\Tasks\Task;

class UpdateController extends BaseController
{
    public function __invoke(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->validated();

        $statusChanged   = isset($data['status'])   && $data['status']   !== $task->status->value;
        $priorityChanged = isset($data['priority'])  && $data['priority']  !== $task->priority->value;
        $assigneeChanged = array_key_exists('assigned_to_user_id', $data)
            && $data['assigned_to_user_id'] != $task->assigned_to_user_id;

        $oldStatus   = $task->status->label();
        $oldPriority = $task->priority->label();
        $oldAssignee = $task->assignedTo?->full_name ?? 'Unassigned';

        if ($statusChanged && $data['status'] === 'completed') {
            $data['completed_at'] = now();
        } elseif ($statusChanged && $task->status->value === 'completed') {
            $data['completed_at'] = null;
        }

        $task->update($data);
        $task->refresh();

        if ($statusChanged)   $task->logActivity('status_changed',   $oldStatus,   $task->status->label());
        if ($priorityChanged) $task->logActivity('priority_changed', $oldPriority, $task->priority->label());
        if ($assigneeChanged) $task->logActivity('reassigned',       $oldAssignee, $task->assignedTo?->full_name ?? 'Unassigned');

        return response()->json(['success' => true, 'message' => 'Task updated.']);
    }
}
