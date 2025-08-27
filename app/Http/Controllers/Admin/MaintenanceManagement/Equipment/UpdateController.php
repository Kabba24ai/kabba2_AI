<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipment\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();

        $equipment->update($request->validated());

        return redirect()
            ->route('admin.maintenance-management.equipment.index')
            ->with('success', 'Equipment updated successfully!');
    }
}
