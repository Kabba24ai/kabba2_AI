<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ProductManagement\Product;
use App\Models\MaintenanceManagement\Part;


class CreateController extends Controller
{
    public function __invoke()
    {

       // Load categories with sorted relationships
        $categories = ProductCategory::with([
            'products',
            'equipments' => function ($q) {
                $q->orderBy('equipment_name', 'asc'); // SORT HERE
            }
        ])->get();


        $parts = Part::with('category')->get();

   
         // Add specific assignment status
//    foreach ($parts as $part) {
  
//     // Assigned globally?
//     $assignedGlobal = $part->isAssigned();

 
//     // New variable for blade:
//     $part->assigned_other = $assignedGlobal;
// }

// dd($categories);



        return view('admin.maintenance_management.parts.parts_list.create')->with('parts', $parts)->with('categories', $categories);
    }
}

