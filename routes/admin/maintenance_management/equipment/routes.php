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
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\FetchWithCatController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\AssignChecklistMasterController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\AssignStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\ServiceController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\WorksheetController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\WorksheetUpdateController;

use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CopyController;

use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\GetPartslistsController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\GenerateController as SpecGenerateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\ApproveController as SpecApproveController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\SaveController as SpecSaveController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria\IndexController as CriteriaIndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria\StoreController as CriteriaStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria\UpdateController as CriteriaUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\CriticalMatchingCriteria\DeleteController as CriteriaDeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\Equipment\Specification\KeyComparisonController as KeyComparisonController;



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

        // Service
        Route::get('/service/{unique_id}', ServiceController::class)->name('service');

        // Delete
        Route::delete('/{unique_id}', DeleteController::class)->name('delete');

        Route::get('/fetch-equipment', FetchController::class)->name('fetch');
        Route::get('/fetch-categories-equipments', FetchWithCatController::class)->name('fetch-with-categories');

        Route::post('/checklist-master-assign', AssignChecklistMasterController::class)->name('checklist-master-assign');
        Route::post('/store-assign', AssignStoreController::class)->name('store-assign');

        Route::get('/{unique_id}/copy', CopyController::class)->name('copy');

        Route::get('/get-parts-lists/{category}',GetPartslistsController::class)->name('get-parts-lists');

        // Specification AI
        Route::post('/{unique_id}/specification/generate', SpecGenerateController::class)->name('specification.generate');
        Route::post('/{unique_id}/specification/{spec_id}/approve', SpecApproveController::class)->name('specification.approve');
        Route::put('/{unique_id}/specification/{spec_id}', SpecSaveController::class)->name('specification.save');

        // Critical Matching Criteria
        Route::get('/{unique_id}/critical-matching-criteria', CriteriaIndexController::class)->name('critical-matching-criteria.index');
        Route::post('/{unique_id}/critical-matching-criteria', CriteriaStoreController::class)->name('critical-matching-criteria.store');
        Route::put('/{unique_id}/critical-matching-criteria/{criteria_id}', CriteriaUpdateController::class)->name('critical-matching-criteria.update');
        Route::delete('/{unique_id}/critical-matching-criteria/{criteria_id}', CriteriaDeleteController::class)->name('critical-matching-criteria.delete');

        // Key Comparisons
        Route::get('/{unique_id}/key-comparisons', [KeyComparisonController::class, 'index'])->name('key-comparisons.index');
        Route::post('/{unique_id}/key-comparisons', [KeyComparisonController::class, 'store'])->name('key-comparisons.store');
        Route::delete('/{unique_id}/key-comparisons/{comparison_id}', [KeyComparisonController::class, 'destroy'])->name('key-comparisons.destroy');
        Route::put('/{unique_id}/key-comparisons/reorder', [KeyComparisonController::class, 'reorder'])->name('key-comparisons.reorder');
    });
