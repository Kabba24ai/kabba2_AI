<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\ProductManagement\ProductCategory;

class CreateController extends Controller
{
    public function __invoke()
    {
        $categories = ProductCategory::getHierarchy();
        $checklistMasters = ChecklistMaster::oldest('checklist_system_name')->pluck('checklist_system_name', 'id')->prepend('Select Checklist Template', '');
        return view('admin.maintenance_management.equipment.create', compact('categories', 'checklistMasters'));
    }
}
