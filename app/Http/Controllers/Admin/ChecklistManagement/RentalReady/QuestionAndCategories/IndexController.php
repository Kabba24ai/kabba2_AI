<?php

namespace App\Http\Controllers\Admin\ChecklistManagement\RentalReady\QuestionAndCategories;

use App\Http\Controllers\Controller;


class IndexController extends Controller
{
    public function __invoke()
    {

        // Return the view with the settings data
        return view('admin.checklist_management.rental_ready.question_and_categories.index');
    }
}
