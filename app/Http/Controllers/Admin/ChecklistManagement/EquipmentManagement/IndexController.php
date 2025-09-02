<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;


class IndexController extends Controller
{
    public function __invoke()
    {
        $users = User::where('status', 'Active')->get();

        return view('admin.checklist_management.equipment_management.index',['users' => $users]);
    }
}
