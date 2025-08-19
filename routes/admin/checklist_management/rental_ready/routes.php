<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\IndexController;



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

Route::prefix('rental-ready')
->name('rental-ready.')
->group(function ($router) {

    
     Route::get('/', IndexController::class)->name('index');


      // question
    require base_path('routes/admin/checklist_management/rental_ready/question/routes.php');

      // question
    require base_path('routes/admin/checklist_management/rental_ready/categories/routes.php');

});
