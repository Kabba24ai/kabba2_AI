<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;

class ShowController extends Controller
{
    public function __invoke($id)
    {
        $template = ServiceTemplate::with(['preset', 'templateTasks.task.category'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'template' => $template,
        ]);
    }
}
