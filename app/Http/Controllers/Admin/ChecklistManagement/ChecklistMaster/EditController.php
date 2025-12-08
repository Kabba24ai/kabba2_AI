<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;


class EditController extends Controller
{

    public function __invoke(Request $request, $unique_id)
    {

        $equipmentCategories = ProductCategory::getHierarchy();

        $checklisttemplate = RentalReadyChecklistTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->where('active_template', 1)->get();

        $checklistmaster = ChecklistMaster::with('category', 'rentalReadyTemplate')->where('unique_id', $unique_id)->first();


        $customeradmintemplate = CustomerAdminTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->where('active_template', 1)->get();


        return view('admin.checklist_management.checklist_master.edit', compact('equipmentCategories', 'checklisttemplate', 'checklistmaster', 'customeradmintemplate'));
    }
}
