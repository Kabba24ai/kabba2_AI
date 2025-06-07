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

Route::prefix('terms-and-condition')
->name('terms-and-condition.')
->group(function ($router) {

    // terms and condition
    require base_path('routes/admin/terms_and_condition/terms/routes.php');
});
