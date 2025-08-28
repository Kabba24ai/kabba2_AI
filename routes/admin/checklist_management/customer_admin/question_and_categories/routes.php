<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\CustomerAdmin\QuestionAndCategories\IndexController;


Route::prefix('question_and_categories')
->name('question_and_categories.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
});
