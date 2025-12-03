<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\ServiceMaster\Template\StoreRequest;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use Illuminate\Support\Facades\DB;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $template = ServiceTemplate::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'preset_id' => $validated['preset_id'],
            ]);

            // Attach tasks with intervals
            foreach ($validated['tasks'] as $taskData) {
                $template->templateTasks()->create([
                    'task_id' => $taskData['task_id'],
                    'intervals' => $taskData['intervals'] ?? [],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'template' => $template->load(['preset', 'templateTasks.task']),
                'message' => 'Template created successfully',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to create template: ' . $e->getMessage(),
            ], 500);
        }
    }
}
