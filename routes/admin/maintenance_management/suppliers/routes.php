<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\IndexController;


Route::prefix('suppliers')
    ->name('suppliers.')
    ->group(function ($router) {

        Route::get('/', IndexController::class)->name('index');


    });
