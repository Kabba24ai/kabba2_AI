<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\AssignChecklistMasterRequest;

// Models
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Services\ChecklistManagement\ChecklistAssignmentService;

class AssignChecklistMasterController extends Controller
{
    public function __construct(private ChecklistAssignmentService $checklistAssignmentService)
    {
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Contracts\View\View
     */
    public function __invoke(AssignChecklistMasterRequest $request)
    {
        $validated = $request->validated();

        $checklistMaster = ChecklistMaster::where('unique_id', $validated['checklist_master_unique_id'])->first();
        $equipment = Equipment::where('unique_id', $validated['equipment_unique_id'])->first();

        if (!$checklistMaster || !$equipment) {
            return response()->json([
                'success' => false,
                'message' => 'Checklist Master or Equipment not found.',
            ], 404);
        }

        $assigned = $this->checklistAssignmentService->assignSingle($equipment, $checklistMaster);

        if (!$assigned) {
            return response()->json([
                'success' => false,
                'message' => 'This Checklist Master is already assigned to the Equipment.',
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'Checklist Master assigned successfully!',
        ]);
    }
}
