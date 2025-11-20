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

Route::prefix('hrm')
->name('hrm.')
->group(function ($router) {

    // customers
    require base_path('routes/admin/hrm/roles/routes.php');

    require base_path('routes/admin/hrm/users/routes.php');

});
