<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;

class EditController extends Controller
{
    public function __invoke(Supplier $unique_id)
    {
        $supplier = $unique_id; // Laravel route model binding
        return view('admin.maintenance_management.suppliers.edit', compact('supplier'));
    }
}
