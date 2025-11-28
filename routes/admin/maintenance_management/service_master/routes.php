<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TaskController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\CategoryController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\PresetController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TemplateController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\SettingsController;

Route::prefix('service-master')->name('service-master.')->group(function () {

// Main service master page
Route::get('/', [IndexController::class, 'index'])->name('index');

// Tasks
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('/', [TaskController::class, 'store'])->name('store');
    Route::put('/{id}', [TaskController::class, 'update'])->name('update');
    Route::delete('/{id}', [TaskController::class, 'destroy'])->name('destroy');
});

// Categories
Route::prefix('categories')->name('categories.')->group(function () {
    Route::post('/', [CategoryController::class, 'store'])->name('store');
    Route::put('/{id}', [CategoryController::class, 'update'])->name('update');
    Route::delete('/{id}', [CategoryController::class, 'destroy'])->name('destroy');
});

// Templates
Route::prefix('templates')->name('templates.')->group(function () {
    Route::post('/', [TemplateController::class, 'store'])->name('store');
    Route::get('/{id}', [TemplateController::class, 'show'])->name('show');
    Route::delete('/{id}', [TemplateController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/tasks', [TemplateController::class, 'addTask'])->name('tasks.add');
});

// Template Tasks
Route::prefix('template-tasks')->name('template-tasks.')->group(function () {
    Route::put('/{id}/intervals', [TemplateController::class, 'updateTaskIntervals'])->name('intervals.update');
    Route::delete('/{id}', [TemplateController::class, 'removeTask'])->name('destroy');
});

// Interval Presets
Route::prefix('presets')->name('presets.')->group(function () {
    Route::post('/', [PresetController::class, 'store'])->name('store');
    Route::delete('/{id}', [PresetController::class, 'destroy'])->name('destroy');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::put('/', [SettingsController::class, 'update'])->name('update');
});

}); // End of service-master group