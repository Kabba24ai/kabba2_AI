<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;
use App\Models\MaintenanceManagement\PartsList;
use App\Models\ProductManagement\ProductCategory;


class EditController extends Controller
{
    public function __invoke($unique_id)
    {

        $list = PartsList::with(['creator', 'category', 'parts'])
            ->where('unique_id', $unique_id)
            ->first();

        // $categories = ProductCategory::with('products', 'equipments')->get();
        // Load categories with sorted relationships
        $categories = ProductCategory::with([
            'products',
            'equipments' => function ($q) {
                $q->orderBy('equipment_name', 'asc'); // SORT HERE
            }
        ])->get();

        $parts = Part::with('category')->get();

//          // Add specific assignment status
//    foreach ($parts as $part) {
//     // Assigned to this current list?
//     $assignedToThis = $part->isAssigned($list->id);

//     // Assigned globally?
//     $assignedGlobal = $part->isAssigned();

//     // Override assigned field meaning for edit mode:
//     // assigned == assigned to THIS list
//     $part->assigned = $assignedToThis;

//     // New variable for blade:
//     $part->assigned_other = $assignedGlobal && !$assignedToThis;
// }



        $selectedPartIds = $list->parts->pluck('id')->toArray();

        return view('admin.maintenance_management.parts.parts_list.edit', compact('list', 'categories', 'parts', 'selectedPartIds'));
    }
}
