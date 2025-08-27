<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster;

use App\Http\Controllers\Controller;
use App\Models\ProductManagement\ProductCategory;
use App\Models\ChecklistManagement\ChecklistMaster\ChecklistMaster;


class IndexController extends Controller
{
    public function __invoke()
    {

        $checklistMasters = ChecklistMaster::with('category', 'rentalReadyTemplate')->get();

        $equipmentCategories = ProductCategory::pluck('title', 'slug')->toArray();


        // Return the view with the settings data
        return view('admin.checklist_management.checklist_master.index',compact('checklistMasters', 'equipmentCategories')  );
    }
}
