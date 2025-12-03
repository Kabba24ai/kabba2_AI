<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template\UpdateRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id)
    {
        $template = ServiceTemplate::findOrFail($id);

        $validated = $request->validated();

        $template->update($validated);

        return response()->json([
            'success' => true,
            'template' => $template->fresh()->load(['preset', 'templateTasks.task']),
            'message' => 'Template updated successfully',
        ]);
    }
}
