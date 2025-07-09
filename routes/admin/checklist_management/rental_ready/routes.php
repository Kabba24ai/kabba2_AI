<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('rental_ready')
->name('rental_ready.')
->group(function ($router) {

    // customers
    require base_path('routes/admin/checklist_management/rental_ready/question_and_categories/routes.php');
});
