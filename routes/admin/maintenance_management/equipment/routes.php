<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CreateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\StoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\EditController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\UpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\DeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\FetchController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\AssignChecklistMasterController;

Route::prefix('equipment')
    ->name('equipment.')
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

        Route::get('/fetch-equipment', FetchController::class)->name('fetch');

        Route::post('/checklist-master-assign', AssignChecklistMasterController::class)->name('checklist-master-assign');

    });
