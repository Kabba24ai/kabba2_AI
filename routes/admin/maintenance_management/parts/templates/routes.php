<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Templates\CreateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\Templates\ViewController;
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

Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::get('/view', ViewController::class)->name('view');

});
