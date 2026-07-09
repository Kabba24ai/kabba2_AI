<?php

use App\Http\Controllers\Admin\Warranty\Cases;
use App\Http\Controllers\Admin\Warranty\Fee;
use Illuminate\Support\Facades\Route;

// Warranty — administrative relationship with the manufacturer. The linked
// Service Ticket performs the technical repair. No billing / payment routes.
Route::prefix('warranty')
    ->name('warranty.')
    ->group(function () {
        Route::prefix('claims')->name('claims.')->group(function () {
            Route::get('/',        Cases\IndexController::class)->name('index');
            Route::get('/create',  Cases\CreateController::class)->name('create');
            Route::post('/',       Cases\StoreController::class)->name('store');
            Route::get('/{case}',  Cases\ShowController::class)->name('show');

            Route::post('/{case}/complete-intake', Cases\CompleteIntakeController::class)->name('complete-intake');
            Route::put('/{case}/fee',              Fee\UpdateController::class)->name('fee.update');
        });
    });
