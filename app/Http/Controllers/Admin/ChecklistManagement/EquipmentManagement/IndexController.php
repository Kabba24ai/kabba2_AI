<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;
use Illuminate\Support\Facades\DB;


class IndexController extends Controller
{
    public function __invoke($equipment = null)
    {
        $users = User::where('status', 'Active')->get();

        $equipments = Equipment::with(['productCategory', 'latestRentalReadyTemplate', 'orderProduct', 'orderProduct.order','order', 'serviceTemplate.preset', 'serviceTemplate.templateTasks.task'])->where('not_for_rent', 0)->orderBy('equipment_name', 'asc')->get();

        $categories = ProductCategory::getHierarchy();

        // Load service settings
        $settings = DB::table('service_master_settings')->first();
        $pendingBeforeHours = $settings->pending_before_hours ?? 20;
        $pendingAfterHours = $settings->pending_after_hours ?? 15;

        // Load all service records
        $serviceRecords = DB::table('equipment_service_tasks')
            ->select('equipment_id', 'service_task_id', 'interval_value')
            ->get()
            ->groupBy(function($record) {
                return $record->equipment_id . '_' . $record->service_task_id;
            });

        // dd($equipments);

        return view('admin.checklist_management.equipment_management.index', [
            'users' => $users,
            'equipments' => $equipments,
            'categories' => $categories,
            'selectedEquipmentId' => $equipment,
            'serviceRecords' => $serviceRecords,
            'pendingBeforeHours' => $pendingBeforeHours,
            'pendingAfterHours' => $pendingAfterHours,
        ]);
    }
}
