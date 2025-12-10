<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use App\Helpers\ModelHelper;

class CopyController extends Controller
{
    public function __invoke($unique_id)
    {
        // Get original equipment
        $equipment = Equipment::with('serviceTemplate')->where('unique_id', $unique_id)->firstOrFail();

        // Create a COPY
        $equipmentCopy = $equipment->replicate();

        // Generate new unique ID
        $equipmentCopy->unique_id = ModelHelper::generateUniqueID(new Equipment, 'EQP');

        // Generate a unique suffix for equipment_id
        $suffix = strtoupper(substr(uniqid(), -4)); // e.g. "A3F9"

        // Always unique equipment_id
        $equipmentCopy->equipment_id = $equipment->equipment_id . '-' . $suffix;
        // Save NEW record
        $equipmentCopy->save();

        // Redirect to EDIT page of the new equipment
        return redirect()->route(
            'admin.maintenance-management.equipment.edit',
            $equipmentCopy->unique_id
        )->with('success', 'Equipment copied successfully!');
    }
}
