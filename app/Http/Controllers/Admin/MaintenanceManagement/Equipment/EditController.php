<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;

class EditController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();
        $categories = ProductCategory::getHierarchy();
        $checklistMasters = ChecklistMaster::oldest('checklist_system_name')->pluck('checklist_system_name', 'id')->prepend('Select Checklist Template', '');
        return view('admin.maintenance_management.equipment.edit', compact('equipment', 'categories', 'checklistMasters'));
    }
}
