<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\IndexController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\ChecklistQuestionsController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\RentalReadyHistoryController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\RentalReadyHistoryShowController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\RentalReadyLatestCompletedController;

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

Route::prefix('equipment-management')
    ->name('equipment-management.')
    ->group(function ($router) {
        Route::get('/', IndexController::class)->name('index');

        // Phase 2B — read-only Rental Ready inspection history + detail.
        // Multi-segment, so they never collide with the /{equipment} wildcard;
        // both bind by unique_id (resolved in the controller, matching the
        // module convention).
        Route::get('/{equipment}/rental-ready-history', RentalReadyHistoryController::class)->name('rental-ready-history');
        Route::get('/{equipment}/rental-ready-history/{template}', RentalReadyHistoryShowController::class)->name('rental-ready-history.show');

        // Phase 3A — read-only data source for the right-panel latest-completed
        // comparison. Multi-segment, so it never collides with /{equipment}.
        Route::get('/{equipment}/rental-ready-latest-completed', RentalReadyLatestCompletedController::class)->name('rental-ready-latest-completed');

        Route::get('/{equipment}', IndexController::class)->name('show');

        Route::post('/get-checklist-questions', ChecklistQuestionsController::class)->name('get-checklist-questions');

        Route::post('/store', StoreController::class)->name('store');
    });
