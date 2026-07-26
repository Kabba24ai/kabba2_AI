<?php

use App\Http\Controllers\Admin\FieldService\Decision;
use App\Http\Controllers\Admin\FieldService\Dispatch;
use App\Http\Controllers\Admin\FieldService\Media;
use App\Http\Controllers\Admin\FieldService\Notes;
use App\Http\Controllers\Admin\FieldService\Tickets;
use Illuminate\Support\Facades\Route;

// Field Service — mission-based dispatch workflow, separate from Shop
// Service (service-management). No billing / Financial Engine routes.
Route::prefix('field-service')
    ->name('field-service.')
    ->group(function () {
        // ST-3: view = read; manage = create/dispatch/decision/notes/media.
        // Inert until the global Gate::before bypass is lifted (see
        // Iam\ModuleSeeder + AppServiceProvider).
        Route::prefix('tickets')->name('tickets.')
            ->middleware('permission:field_service.view')
            ->group(function () {
            // No field index — the Operations Board is the single workload
            // surface (filter to Field). Only intake + the mission workbench
            // live here.
            Route::get('/create',           Tickets\CreateController::class)->name('create');
            Route::post('/',                Tickets\StoreController::class)->name('store')->middleware('permission:field_service.manage');
            Route::get('/{ticket}',         Tickets\ShowController::class)->name('show');
            Route::post('/{ticket}/status',   Tickets\StatusController::class)->name('status')->middleware('permission:field_service.manage');
            Route::post('/{ticket}/decision', Decision\StoreController::class)->name('decision.store')->middleware('permission:field_service.manage');

            Route::put('/{ticket}/dispatch', Dispatch\UpdateController::class)->name('dispatch.update')->middleware('permission:field_service.manage');
            Route::put('/{ticket}/media',    Media\UpdateController::class)->name('media.update')->middleware('permission:field_service.manage');
            Route::post('/{ticket}/notes',   Notes\StoreController::class)->name('notes.store')->middleware('permission:field_service.manage');
        });
    });
