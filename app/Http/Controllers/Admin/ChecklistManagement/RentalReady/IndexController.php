<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistTemplate;
use App\Models\ProductManagement\ProductCategory;


class IndexController extends Controller
{
    public function __invoke()
    {

        $rentalreadycategory = RentalReadyChecklistCategory::withCount('questions')
        ->with(['questions.answers'])
        ->get();

        $totalQuestions = RentalReadyChecklistQuestion::count();



        $checklisttemplate = RentalReadyChecklistTemplate::with([
            'questions',
        ])->orderBy('template_name', 'asc')->get();


    // $equipmentCategories = ProductCategory::pluck('title','id')->toArray();
        $equipmentCategories = ProductCategory::getHierarchy();


        // Return the view with the settings data
        return view('admin.checklist_management.rental_ready.index',compact('rentalreadycategory','totalQuestions','equipmentCategories','checklisttemplate'));
    }
}
