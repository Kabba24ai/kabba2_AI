<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Part;
use App\Models\MaintenanceManagement\Supplier;

class FetchSupplierController extends Controller
{
    public function __invoke($unique_id)
    {
        $supplier = Supplier::where('unique_id',$unique_id)->first();

        if (!$supplier) {
            return response()->json(['error' => 'Supplier not found'], 404);
        }

        return response()->json([
            'name' => $supplier->name,
            'address' => $supplier->full_address,
            'phone' => $supplier->primary_contact_phone,
            'contact_person' => $supplier->primary_contact_name,
            'email' => $supplier->email,
        ]);
    }
}
