<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;

class CreateController extends Controller
{
    public function __invoke()
    {
        
        $equipmentCategories = ProductCategory::getHierarchy();

        $checklisttemplate = RentalReadyChecklistTemplate::with([
            'questions',
        ])->orderBy('id', 'desc')->get();


        // Return the view with the settings data
        return view('admin.checklist_management.checklist_master.create',compact('equipmentCategories','checklisttemplate'));
    }
}
