<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminCategory;

use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestion;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminQuestionAnswer;
use App\Models\ChecklistManagement\CustomerAdmin\CustomerAdminTemplate;
use App\Models\ProductManagement\ProductCategory;

class IndexController extends Controller
{
    public function __invoke()
    {
        $customerAdminCategory = CustomerAdminCategory::withCount('questions')
    ->with(['questions.answers'])
    ->get();

        $totalQuestions = CustomerAdminQuestion::count();


        $checklisttemplate = CustomerAdminTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->get();


    //$equipmentCategories = ProductCategory::pluck('title','id')->toArray();
    $equipmentCategories = ProductCategory::getHierarchy();


        // Return the view with the settings data
        return view('admin.checklist_management.customer_admin.index', compact(
    'customerAdminCategory', 
    'totalQuestions',
    'equipmentCategories',
    'checklisttemplate'
));
/*
        //return view('admin.checklist_management.rental_ready.index',compact('rentalreadycategory','totalQuestions','equipmentCategories','checklisttemplate'));
        //$CustomerAdminCategory = CustomerAdminCategory::all();
        //return view('admin.checklist_management.customer_admin.index', compact('CustomerAdminCategory'));
*/

    }
}
