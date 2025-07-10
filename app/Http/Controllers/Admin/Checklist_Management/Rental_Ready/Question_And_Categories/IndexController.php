<?php

namespace App\Http\Controllers\Admin\Checklist_Management\Rental_Ready\Question_And_Categories;

use App\Http\Controllers\Controller;


class IndexController extends Controller
{
    public function __invoke()
    {

        // Return the view with the settings data
        return view('admin.checklist_management.rental_ready.question_and_categories.index');
    }
}
