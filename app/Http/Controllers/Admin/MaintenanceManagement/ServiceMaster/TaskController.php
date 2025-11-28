<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTask;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:service_categories,id',
            'auto_apply' => 'boolean',
        ]);

        $validated['category_id'] = $validated['category_id'] ?? null;
        $validated['auto_apply'] = $validated['auto_apply'] ?? false;

        $task = ServiceTask::create($validated);

        return response()->json([
            'success' => true,
            'task' => $task->load('category'),
            'message' => 'Task created successfully',
        ]);
    }

    public function update(Request $request, $id)
    {
        $task = ServiceTask::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'estimated_duration' => 'nullable|integer|min:0',
            'category_id' => 'nullable|exists:service_categories,id',
            'auto_apply' => 'boolean',
        ]);

        $validated['category_id'] = $validated['category_id'] ?? null;
        $validated['auto_apply'] = $validated['auto_apply'] ?? false;

        $task->update($validated);

        return response()->json([
            'success' => true,
            'task' => $task->load('category'),
            'message' => 'Task updated successfully',
        ]);
    }

    public function destroy($id)
    {
        $task = ServiceTask::findOrFail($id);
        $task->delete();

        return response()->json([
            'success' => true,
            'message' => 'Task deleted successfully',
        ]);
    }
}

