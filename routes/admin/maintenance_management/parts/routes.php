<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\CreateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\EditController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\DeleteController;

Route::prefix('parts')
    ->name('parts.')
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
