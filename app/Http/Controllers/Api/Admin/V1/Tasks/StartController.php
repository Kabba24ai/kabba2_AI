<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;

class StartController extends BaseController
{
    public function __invoke(Task $task)
    {
        if ($task->status->value === 'in_progress') {
            return response()->json(['success' => false, 'message' => 'Task is already in progress.'], 422);
        }

        $old = $task->status->label();
        $task->update(['status' => 'in_progress']);
        $task->logActivity('status_changed', $old, 'In Progress');

        return response()->json(['success' => true, 'message' => 'Task started.']);
    }
}
