<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\IndexController;

// Task Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task\StoreController as TaskStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task\UpdateController as TaskUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Task\DestroyController as TaskDestroyController;

// Category Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category\StoreController as CategoryStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category\UpdateController as CategoryUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Category\DestroyController as CategoryDestroyController;

// Template Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template\StoreController as TemplateStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template\ShowController as TemplateShowController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template\UpdateController as TemplateUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template\DestroyController as TemplateDestroyController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Template\AddTaskController as TemplateAddTaskController;

// Template Task Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TemplateTask\UpdateIntervalsController as TemplateTaskUpdateIntervalsController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\TemplateTask\DestroyController as TemplateTaskDestroyController;

// Preset Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset\StoreController as PresetStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset\UpdateController as PresetUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Preset\DestroyController as PresetDestroyController;

// Settings Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\ServiceMaster\Settings\UpdateController as SettingsUpdateController;

Route::prefix('service-master')->name('service-master.')->group(function () {

// Main service master page
Route::get('/', [IndexController::class, 'index'])->name('index');

// Tasks
Route::prefix('tasks')->name('tasks.')->group(function () {
    Route::post('/', TaskStoreController::class)->name('store');
    Route::put('/{id}', TaskUpdateController::class)->name('update');
    Route::delete('/{id}', TaskDestroyController::class)->name('destroy');
});

// Categories
Route::prefix('categories')->name('categories.')->group(function () {
    Route::post('/', CategoryStoreController::class)->name('store');
    Route::put('/{id}', CategoryUpdateController::class)->name('update');
    Route::delete('/{id}', CategoryDestroyController::class)->name('destroy');
});

// Templates
Route::prefix('templates')->name('templates.')->group(function () {
    Route::post('/', TemplateStoreController::class)->name('store');
    Route::get('/{id}', TemplateShowController::class)->name('show');
    Route::put('/{id}', TemplateUpdateController::class)->name('update');
    Route::delete('/{id}', TemplateDestroyController::class)->name('destroy');
    Route::post('/{id}/tasks', TemplateAddTaskController::class)->name('tasks.add');
});

// Template Tasks
Route::prefix('template-tasks')->name('template-tasks.')->group(function () {
    Route::put('/{id}/intervals', TemplateTaskUpdateIntervalsController::class)->name('intervals.update');
    Route::delete('/{id}', TemplateTaskDestroyController::class)->name('destroy');
});

// Interval Presets
Route::prefix('presets')->name('presets.')->group(function () {
    Route::post('/', PresetStoreController::class)->name('store');
    Route::put('/{id}', PresetUpdateController::class)->name('update');
    Route::delete('/{id}', PresetDestroyController::class)->name('destroy');
});

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::put('/', SettingsUpdateController::class)->name('update');
});

}); // End of service-master group