<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\StoreRequest;
use App\Models\MaintenanceManagement\Supplier;

class StoreController extends Controller
{
    public function __invoke(StoreRequest $request)
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('admin.maintenance-management.suppliers.index')
            ->with('success', 'Supplier created successfully.');
    }
}
