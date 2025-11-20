<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;

class EditController extends Controller
{
    public function __invoke($id)
    {
        $supplier = Supplier::with(['state', 'category', 'media'])->findOrFail($id);

        // Load tag objects 
        $supplier->tag_objects = $supplier->tag_objects ?? [];

        return response()->json([
            'status' => true,
            'data' => $supplier,
        ]);
    }
}
