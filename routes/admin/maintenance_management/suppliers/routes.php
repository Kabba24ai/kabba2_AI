<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\CreateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\EditController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Suppliers\DeleteController;

Route::prefix('suppliers')
    ->name('suppliers.')
    ->group(function ($router) {

        Route::get('/', IndexController::class)->name('index');
        
        // Create
        Route::get('/create', CreateController::class)->name('create');
        Route::post('/create', StoreController::class);
        
        // Edit
        Route::get('/{unique_id}/edit', EditController::class)->name('edit');
        Route::put('/{unique_id}/edit', UpdateController::class);
        
        // Delete
        Route::delete('/{unique_id}', DeleteController::class)->name('delete');
    });
