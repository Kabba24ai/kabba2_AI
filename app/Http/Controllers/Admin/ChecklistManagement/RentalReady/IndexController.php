<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady;

use App\Http\Controllers\Controller;


class IndexController extends Controller
{
    public function __invoke()
    {

        // Return the view with the settings data
        return view('admin.checklist_management.rental_ready.index');
    }
}
