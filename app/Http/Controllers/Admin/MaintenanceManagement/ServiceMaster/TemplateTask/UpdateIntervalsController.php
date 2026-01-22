<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TemplateTask;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\TemplateTask\UpdateIntervalsRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplateTask;

class UpdateIntervalsController extends Controller
{
    public function __invoke(UpdateIntervalsRequest $request, $id)
    {
        $templateTask = ServiceTemplateTask::findOrFail($id);

        $validated = $request->validated();

        // Sort intervals in ascending order
        $intervals = isset($validated['intervals']) 
            ? collect($validated['intervals'])->sort()->values()->toArray()
            : [];

        $templateTask->update([
            'intervals' => $intervals,
        ]);

        return response()->json([
            'success' => true,
            'templateTask' => $templateTask->fresh()->load('task'),
            'message' => 'Intervals updated successfully',
        ]);
    }
}
