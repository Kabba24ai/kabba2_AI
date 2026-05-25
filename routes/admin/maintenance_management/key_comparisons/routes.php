<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\IndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria\IndexController as CriteriaIndexController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria\AddController as CriteriaAddController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria\StoreController as CriteriaStoreController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria\UpdateController as CriteriaUpdateController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Criteria\DeleteController as CriteriaDeleteController;
use App\Http\Controllers\Admin\MaintenanceManagement\KeyComparisons\Specifications\GenerateController as SpecificationsGenerateController;

Route::prefix('key-comparisons')
    ->name('key-comparisons.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');

        Route::get('/criteria', CriteriaIndexController::class)->name('criteria.index');
        Route::post('/criteria/{criteria_id}/add', CriteriaAddController::class)->name('criteria.add');
        Route::post('/criteria', CriteriaStoreController::class)->name('criteria.store');
        Route::put('/criteria/{criteria_id}', CriteriaUpdateController::class)->name('criteria.update');
        Route::delete('/criteria/{criteria_id}', CriteriaDeleteController::class)->name('criteria.delete');

        Route::post('/specifications/generate', SpecificationsGenerateController::class)->name('specifications.generate');
    });
