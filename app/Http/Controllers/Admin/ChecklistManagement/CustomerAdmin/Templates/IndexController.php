<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\Templates;

use App\Http\Controllers\Controller;


class IndexController extends Controller
{
    public function __invoke()
    {

        
        // Return the view with the settings data
        return view('admin.checklist_management.customer_admin.templates.index');
    }
}
