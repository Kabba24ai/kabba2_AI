<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;

class IndexController extends Controller
{
    public function __invoke()
    {
        $equipmentWithService = Equipment::with([
                'serviceTemplate.preset',
                'serviceTemplate.templateTasks.task',
                'productCategory'
            ])
            ->whereNotNull('equipment_service_id')
            ->latest()
            ->paginate(15);

        return view('admin.maintenance_management.equipment_service.index', compact('equipmentWithService'));
    }
}
