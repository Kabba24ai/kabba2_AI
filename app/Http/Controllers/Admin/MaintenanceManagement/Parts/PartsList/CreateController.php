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

        $categories = ProductCategory::with('products')->get();

        $parts = Part::with('category')->get();

   
         // Add specific assignment status
   foreach ($parts as $part) {
  
    // Assigned globally?
    $assignedGlobal = $part->isAssigned();

 
    // New variable for blade:
    $part->assigned_other = $assignedGlobal;
}




        return view('admin.maintenance_management.parts.parts_list.create')->with('parts', $parts)->with('categories', $categories);
    }
}

