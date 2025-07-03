<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Suppliers;

use App\Http\Controllers\Controller;

class CreateController extends Controller
{
    public function __invoke()
    {
        return view('admin.maintenance_management.suppliers.create');
    }
}
