<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;

class FetchController extends Controller
{
    public function __invoke()
    {
        $checklistMasters = ChecklistMaster::orderBy('checklist_system_name')->get()->map(function ($equipment) {
            return [
                'unique_id' => $equipment->unique_id,
                'checklist_system_name' => $equipment->checklist_system_name,
            ];
        });

        return response()->json(
            [
                'success' => true,
                'checklistMasters' => $checklistMasters
            ]
        );
    }
}
