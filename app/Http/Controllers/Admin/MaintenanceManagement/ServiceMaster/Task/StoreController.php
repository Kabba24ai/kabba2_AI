<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Task\StoreRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        $validated['category_id'] = $validated['category_id'] ?? null;
        $validated['auto_apply'] = $validated['auto_apply'] ?? false;
        $validated['inspection_required'] = $validated['inspection_required'] ?? false;

        $task = ServiceTask::create($validated);

        return response()->json([
            'success' => true,
            'task' => $task->load('category'),
            'message' => 'Task created successfully',
        ]);
    }
}
