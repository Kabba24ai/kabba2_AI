<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Supplier;

class DeleteController extends Controller
{
    public function __invoke(Supplier $supplier)
    {
        $supplier->delete();

        return redirect()
            ->route('admin.maintenance-management.suppliers.index')
            ->with('success', 'Supplier deleted successfully.');
    }
}
