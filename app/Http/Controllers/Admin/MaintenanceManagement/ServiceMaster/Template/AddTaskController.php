<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template\AddTaskRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;

class AddTaskController extends Controller
{
    public function __invoke(AddTaskRequest $request, $templateId)
    {
        $template = ServiceTemplate::findOrFail($templateId);

        $validated = $request->validated();

        $templateTask = $template->templateTasks()->create([
            'task_id' => $validated['task_id'],
            'intervals' => $validated['intervals'] ?? [],
        ]);

        return response()->json([
            'success' => true,
            'templateTask' => $templateTask->load('task'),
            'message' => 'Task added to template successfully',
        ]);
    }
}
