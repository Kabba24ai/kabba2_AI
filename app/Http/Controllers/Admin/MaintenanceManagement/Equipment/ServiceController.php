<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Equipment;

use App\Http\Controllers\Controller;
use App\Models\MaintenanceManagement\Equipment;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function __invoke($unique_id)
    {
        $equipment = Equipment::with(['serviceTemplate.preset', 'serviceTemplate.templateTasks.task'])
            ->where('unique_id', $unique_id)
            ->firstOrFail();

        return view('admin.maintenance_management.equipment.service', compact('equipment'));
    }
}
