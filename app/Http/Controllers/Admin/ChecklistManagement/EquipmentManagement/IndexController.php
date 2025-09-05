<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\MaintenanceManagement\Equipment;
use App\Models\ProductManagement\ProductCategory;


class IndexController extends Controller
{
    public function __invoke()
    {
        $users = User::where('status', 'Active')->get();

        $equipments = Equipment::with(['productCategory', 'latestRentalReadyTemplate'])->get();

        $categories = ProductCategory::getHierarchy();

        // dd($equipments);

        return view('admin.checklist_management.equipment_management.index', [
            'users' => $users,
            'equipments' => $equipments,
            'categories' => $categories,
        ]);
    }
}
