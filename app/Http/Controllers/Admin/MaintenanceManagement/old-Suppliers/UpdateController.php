<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Suppliers\UpdateRequest;
use App\Models\MaintenanceManagement\Supplier;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, Supplier $unique_id)
    {
        $unique_id->update($request->validated());
        return redirect()
            ->route('admin.maintenance-management.suppliers.index')
            ->with('success', 'Supplier updated successfully.');
    }
}
