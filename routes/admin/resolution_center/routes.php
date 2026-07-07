<?php

use App\Http\Controllers\Admin\ResolutionCenter\AnswerController;
use App\Http\Controllers\Admin\ResolutionCenter\DecisionController;
use App\Http\Controllers\Admin\ResolutionCenter\IndexController;
use App\Http\Controllers\Admin\ResolutionCenter\IssueCreditController;
use App\Http\Controllers\Admin\ResolutionCenter\ManualResolutionAnswerController;
use App\Http\Controllers\Admin\ResolutionCenter\ShowController;
use App\Http\Controllers\Admin\ResolutionCenter\StoreController;
use Illuminate\Support\Facades\Route;

// Phase 3.3 — Customer Resolution Center Foundation. Started from the
// Order Edit screen (order unique_id known there); the case itself is then
// addressed by its own unique_id for every subsequent action.
Route::prefix('resolution-center')
    ->name('resolution-center.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index')->middleware('permission:resolution_center.view_audit_history');
        Route::post('/orders/{order_unique_id}/start', StoreController::class)->name('start')->middleware('permission:resolution_center.use');
        Route::get('/{unique_id}', ShowController::class)->name('show')->middleware('permission:resolution_center.view');
        Route::put('/{unique_id}/answer', AnswerController::class)->name('answer')->middleware('permission:resolution_center.use');
        // Phase 3.5 — Manual Resolution Scenario Foundation.
        Route::put('/{unique_id}/manual-answer', ManualResolutionAnswerController::class)->name('manual-answer')->middleware('permission:resolution_center.use');
        Route::post('/{unique_id}/decision', DecisionController::class)->name('decision')->middleware('permission:resolution_center.use');
        Route::post('/{unique_id}/issue-credit', IssueCreditController::class)->name('issue-credit')->middleware('permission:customer_credit.grant');
    });
