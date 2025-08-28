<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement;

use App\Http\Controllers\Controller;


class IndexController extends Controller
{
    public function __invoke()
    {

        return view('admin.checklist_management.equipment_management.index');
    }
}
