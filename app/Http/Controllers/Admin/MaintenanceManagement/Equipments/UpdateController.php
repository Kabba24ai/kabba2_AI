<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MaintenanceManagement\Equipments\UpdateRequest;
use App\Models\MaintenanceManagement\Equipment;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();
        
        $equipment->update($request->validated());
        
        return redirect()
            ->route('admin.maintenance-management.equipments.index')
            ->with('success', 'Equipment updated successfully!');
    }
}
