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

Route::prefix('checklist-management')
->name('checklist-management.')
->group(function ($router) {

    // customers
    require base_path('routes/admin/checklist_management/rental_ready/routes.php');

    // Checklist_Master
     require base_path('routes/admin/checklist_management/checklist_master/routes.php');

});
