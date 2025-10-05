<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;


class IndexController extends Controller
{
    public function __invoke($equipment = null)
    {
        $users = User::where('status', 'Active')->get();

        $equipments = Equipment::with(['productCategory', 'latestRentalReadyTemplate', 'orderProduct','order'])->orderBy('equipment_name', 'asc')->get();

        $categories = ProductCategory::getHierarchy();

        // dd($equipments->checklistMaster->rentalReadyTemplate->);

        return view('admin.checklist_management.equipment_management.index', [
            'users' => $users,
            'equipments' => $equipments,
            'categories' => $categories,
            'selectedEquipmentId' => $equipment,
        ]);
    }
}
