<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TemplateTask;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask;

class DestroyController extends Controller
{
    public function __invoke($id)
    {
        $templateTask = ServiceTemplateTask::findOrFail($id);
        
        $templateTask->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task removed from template successfully',
        ]);
    }
}
