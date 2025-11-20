<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\AssignChecklistMasterRequest;

// Models
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;

class AssignChecklistMasterController extends Controller
{
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

        if ($equipment->checklist_master_id === $checklistMaster->id) {
            return response()->json([
                'success' => false,
                'message' => 'This Checklist Master is already assigned to the Equipment.',
            ], 400);
        }

        // Assign equipment details to order product
        $equipment->checklist_master_id = $checklistMaster->id;
        $equipment->save();

        return response()->json([
            'success' => true,
            'message' => 'Checklist Master assigned successfully!',
        ]);
    }
}
