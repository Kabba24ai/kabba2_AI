<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Equipment;



class ViewController extends Controller
{
     public function __invoke($unique_id)
    {

        $part = Part::with('category', 'templates',  'templates.category',
          'partsLists',
        'partsLists.category',
            'primarySupplier',
            'alt1Supplier',
            'alt2Supplier')->where('unique_id', $unique_id)->first(); // Laravel route model binding



  // Collect all assigned equipment from all parts lists
        $assignedEquipments = $part->partsLists
            ->flatMap(function ($list) {
                return Equipment::whereIn('id', (array) $list->selected_products)
                    ->get(['equipment_name', 'equipment_id']);
            })
            ->unique('equipment_id');

        return view('admin.maintenance_management.parts.view')->with('part',$part)->with('assignedEquipments',$assignedEquipments);
    }
}
