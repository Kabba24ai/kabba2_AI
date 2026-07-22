<?php

use App\Http\Controllers\Admin\Warranty\Cases;
use App\Http\Controllers\Admin\Warranty\Fee;
use App\Http\Controllers\Admin\Warranty\Workflow;
use Illuminate\Support\Facades\Route;

// Warranty — administrative relationship with the manufacturer. The linked
// Service Ticket performs the technical repair. No billing / payment routes.
Route::prefix('warranty')
    ->name('warranty.')
    ->group(function () {
        // ST-3: view = read; manage = create case/complete-intake/fee.
        // Inert until the global Gate::before bypass is lifted (see
        // Iam\ModuleSeeder + AppServiceProvider).
        Route::prefix('claims')->name('claims.')
            ->middleware('permission:warranty.view')
            ->group(function () {
            Route::get('/',        Cases\IndexController::class)->name('index');
            Route::get('/create',  Cases\CreateController::class)->name('create');
            Route::post('/',       Cases\StoreController::class)->name('store')->middleware('permission:warranty.manage');
            Route::get('/{case}',  Cases\ShowController::class)->name('show');

            Route::post('/{case}/complete-intake', Cases\CompleteIntakeController::class)->name('complete-intake')->middleware('permission:warranty.manage');
            Route::put('/{case}/fee',              Fee\UpdateController::class)->name('fee.update')->middleware('permission:warranty.manage');

            // ST-4 (Phase 2) workflow — the mid-lifecycle work panels.
            Route::post('/{case}/submit',            Workflow\SubmitController::class)->name('submit')->middleware('permission:warranty.manage');
            Route::post('/{case}/oem-decision',      Workflow\OemDecisionController::class)->name('oem-decision')->middleware('permission:warranty.manage');
            Route::post('/{case}/customer-decision', Workflow\CustomerDecisionController::class)->name('customer-decision')->middleware('permission:warranty.manage');
            Route::post('/{case}/repair-complete',   Workflow\RepairCompleteController::class)->name('repair-complete')->middleware('permission:warranty.manage');
            Route::post('/{case}/reimbursement',     Workflow\ReimbursementController::class)->name('reimbursement')->middleware('permission:warranty.manage');
        });
    });
