<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;


class CreateController extends Controller
{
    public function __invoke()
    {

        // Return the view with the settings data
        return view('admin.checklist_management.checklist_master.create');
    }
}
