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
        Route::prefix('tickets')->name('tickets.')->group(function () {
            Route::get('/',                 Tickets\IndexController::class)->name('index');
            Route::get('/create',           Tickets\CreateController::class)->name('create');
            Route::post('/',                Tickets\StoreController::class)->name('store');
            Route::get('/{ticket}',         Tickets\ShowController::class)->name('show');
            Route::post('/{ticket}/status',   Tickets\StatusController::class)->name('status');
            Route::post('/{ticket}/decision', Decision\StoreController::class)->name('decision.store');

            Route::put('/{ticket}/dispatch', Dispatch\UpdateController::class)->name('dispatch.update');
            Route::put('/{ticket}/media',    Media\UpdateController::class)->name('media.update');
            Route::post('/{ticket}/notes',   Notes\StoreController::class)->name('notes.store');
        });
    });
