<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;

class IndexController extends Controller
{
    public function __invoke()
    {
        $CustomerAdminCategory = CustomerAdminCategory::all();

        return view('admin.checklist_management.customer_admin.index', compact('CustomerAdminCategory'));
    }
}
