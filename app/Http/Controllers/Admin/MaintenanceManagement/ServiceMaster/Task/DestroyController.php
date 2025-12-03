<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $task = ServiceTask::findOrFail($id);
        
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }
}
