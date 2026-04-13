<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\EquipmentService;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\Iam\Personnel\User;
use Illuminate\Support\Facades\DB;

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
            ->get()
            ->sortBy(function($item) {
                return strtolower($item->equipment_name);
            });

        // Load all service records
        $serviceRows = DB::table('equipment_service_tasks')
            ->leftJoin('users as performed_user', 'equipment_service_tasks.performed_by', '=', 'performed_user.id')
            ->leftJoin('users as checked_user', 'equipment_service_tasks.checked_by', '=', 'checked_user.id')
            ->select(
                'equipment_service_tasks.*',
                DB::raw('CONCAT(performed_user.first_name, " ", COALESCE(performed_user.last_name, "")) as performed_by_name'),
                DB::raw('CONCAT(checked_user.first_name, " ", COALESCE(checked_user.last_name, "")) as checked_by_name')
            )
            ->get();

        $assignedUserIds = $serviceRows->pluck('performed_by')
            ->merge($serviceRows->pluck('checked_by'))
            ->filter()
            ->unique()
            ->values();

        $users = User::activeOrIds($assignedUserIds)->orderBy('first_name')->get();

        $serviceRecords = $serviceRows
            ->groupBy(function($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });

        $settings = DB::table('service_master_settings')->first();

        return view('admin.maintenance_management.equipment_service.index', compact('equipmentWithService', 'users', 'serviceRecords', 'settings'));
    }
}
