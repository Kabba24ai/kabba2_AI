<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;

class CompleteController extends BaseController
{
    public function __invoke(Task $task)
    {
        if ($task->status->value === 'completed') {
            return response()->json(['success' => false, 'message' => 'Task is already completed.'], 422);
        }

        $old = $task->status->label();
        $task->update(['status' => 'completed', 'completed_at' => now()]);
        $task->logActivity('status_changed', $old, 'Completed');

        return response()->json(['success' => true, 'message' => 'Task completed.']);
    }
}
