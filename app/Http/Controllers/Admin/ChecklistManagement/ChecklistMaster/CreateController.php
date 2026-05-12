<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\MaintenanceManagement\Equipment;

use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;


class CreateController extends Controller
{
    public function __invoke()
    {

        $equipmentCategories = ProductCategory::getHierarchy();

        $checklisttemplate = RentalReadyChecklistTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->where('active_template',1)->get();


        $customeradmintemplate = CustomerAdminTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->where('active_template', 1)->get();


         $equipments = Equipment::orderBy('equipment_name', 'asc')->get();

       
        // Return the view with the settings data
        return view('admin.checklist_management.checklist_master.create',compact('equipmentCategories','checklisttemplate' , 'customeradmintemplate','equipments'));
    }
}
