<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use App\Models\Stores\Store;
use App\Models\MaintenanceManagement\ServiceMaster\ServiceTemplate;
use App\Models\MaintenanceManagement\PartsList;

class EditController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::with('serviceTemplate')->where('unique_id', $unique_id)->firstOrFail();
        $categories = ProductCategory::getHierarchy();
        $checklistMasters = ChecklistMaster::oldest('checklist_system_name')->pluck('checklist_system_name', 'id')->prepend('Select Checklist Template', '');

         $partsLists = PartsList::orderBy('name')
            ->pluck('name', 'id')
            ->prepend('Select Parts List Template', '');

        $stores = Store::pluck('store_name', 'id')->toArray();
        $serviceTemplates = ServiceTemplate::oldest('name')->pluck('name', 'id')->toArray();


//        $selectedPartsListIds = PartsList::whereJsonContains(
//     'selected_products',
//     (string) $equipment->id
// )->pluck('id')->toArray();
// dd($selectedPartsListIds);

 $selectedPartsListId = $equipment->parts_list_id;



        return view('admin.maintenance_management.equipment.edit', compact('equipment', 'categories', 'checklistMasters', 'stores', 'serviceTemplates','partsLists','selectedPartsListId'));
    }
}
