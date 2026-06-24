<?php

namespace App\Http\Controllers\Api\Admin\V1\Tasks;

use App\Http\Controllers\Api\BaseController;
use App\Models\Tasks\Task;

class CancelController extends BaseController
{
    public function __invoke(Task $task)
    {
        if ($task->status->value === 'cancelled') {
            return response()->json(['success' => false, 'message' => 'Task is already cancelled.'], 422);
        }

        $old = $task->status->label();
        $task->update(['status' => 'cancelled']);
        $task->logActivity('status_changed', $old, 'Cancelled');

        return response()->json(['success' => true, 'message' => 'Task cancelled.']);
    }
}
