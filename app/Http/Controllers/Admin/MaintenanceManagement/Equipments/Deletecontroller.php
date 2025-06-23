<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipments;

use App\Http\Controllers\Controller;
use App\Models\Equipment\Equipment;

class DeleteController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::where('unique_id', $unique_id)->firstOrFail();
        $equipment->delete();
        
        return redirect()
            ->route('admin.maintenance-management.equipments.index')
            ->with('success', 'Equipment deleted successfully!');
    }
}
