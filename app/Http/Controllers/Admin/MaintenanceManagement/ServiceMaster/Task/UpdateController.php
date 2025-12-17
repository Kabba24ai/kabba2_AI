<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Task\UpdateRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id)
    {
        $task = ServiceTask::findOrFail($id);

        $validated = $request->validated();

        $validated['category_id'] = $validated['category_id'] ?? null;
        $validated['auto_apply'] = $validated['auto_apply'] ?? false;
        // `validated()` only returns fields with validation rules. We accept `reference_links` from the raw input
        // because we removed explicit validation for it. This preserves arrays sent from the JS form.
        $validated['reference_links'] = $request->input('reference_links', $validated['reference_links'] ?? null);

        $task->update($validated);

        return response()->json([
            'success' => true,
            'task' => $task->fresh()->load('category'),
            'message' => 'Task updated successfully',
        ]);
    }
}
