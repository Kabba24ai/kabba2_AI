<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\IndexController;
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\CreateController;
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\StoreController;
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\EditController;
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\UpdateController;
use App\Http\Controllers\Admin\ProductManagement\EquipmentAssignments\DeleteController;

Route::prefix('equipment-assignments')
->name('equipment-assignments.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class)->name('store');

    // Edit
    Route::get('/{equipmentAssignment}/edit', EditController::class)->name('edit');
    Route::put('/{equipmentAssignment}/edit', UpdateController::class)->name('update');

    // Delete
    Route::delete('/{equipmentAssignment}', DeleteController::class)->name('delete');
});
