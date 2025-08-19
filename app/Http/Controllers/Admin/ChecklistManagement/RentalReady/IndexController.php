<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady;

use App\Http\Controllers\Controller;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistCategory;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestion;
use App\Models\ChecklistManagement\RentalReady\RentalReadyChecklistQuestionAnswer;

class IndexController extends Controller
{
    public function __invoke()
    {

        $rentalreadycategory = RentalReadyChecklistCategory::withCount('questions')
        ->with(['questions.answers'])
        ->get();

        $totalQuestions = RentalReadyChecklistQuestion::count();
        // Return the view with the settings data
        return view('admin.checklist_management.rental_ready.index',compact('rentalreadycategory','totalQuestions'));
    }
}
