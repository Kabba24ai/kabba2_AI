<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;

class ViewController extends Controller
{
    public function __invoke($id)
    {
        $supplier = Supplier::with(['state', 'category', 'media'  ,'primaryParts.partsLists.category',
    'alt1Parts.partsLists.category',
    'alt2Parts.partsLists.category' ])->findOrFail($id);



    // Append computed attribute
    $supplier->append('all_supplied_parts');

        // Load tag objects (if using IDs like "1,4")
        $supplier->tag_objects = $supplier->tag_objects ?? [];

        return response()->json([
            'status' => true,
            'data' => $supplier,
        ]);
    }
}
