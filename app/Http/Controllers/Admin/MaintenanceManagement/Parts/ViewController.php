<?php

namespace App\Http\Controllers\Admin\MaintenanceManagement\Parts;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ViewController extends Controller
{
    //
     public function __invoke()
    {
        return view('admin.maintenance_management.parts.view');
    }
}
