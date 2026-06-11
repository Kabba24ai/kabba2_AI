<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Dashboard\IndexController;
use App\Http\Controllers\Admin\Dashboard\NotesStoreController;
use App\Http\Controllers\Admin\Dashboard\AmountUpdateController;
use App\Http\Controllers\Admin\Dashboard\PaymentStoreController;
use App\Http\Controllers\Admin\Dashboard\MarkUncollectibleController;
use App\Http\Controllers\Admin\Dashboard\MarkResolvedController;
use App\Http\Controllers\Admin\Dashboard\ExtraChargesShowController
;
use App\Http\Controllers\Admin\Dashboard\CallNeededStoreController;
use App\Http\Controllers\Admin\Dashboard\CallNeededListController;
use App\Http\Controllers\Admin\Dashboard\CallNeededShowController;
use App\Http\Controllers\Admin\Dashboard\CallNeededClearController;

use App\Http\Controllers\Admin\Dashboard\CallNeededCompleteController;

use App\Http\Controllers\Admin\Dashboard\CallNeededUpdateController;


Route::prefix('dashboard')
->name('dashboard.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');

    Route::post('/notes-store/{unique_id}', NotesStoreController::class)->name('notes.store');

    Route::post('/amount-update/{unique_id}', AmountUpdateController::class)->name('amount.update');

    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

      Route::post('/extra-charges/uncollectible/{unique_id}', MarkUncollectibleController::class)->name('extra-charges.uncollectible');

      Route::post('/extra-charges/resolved/{id}', MarkResolvedController::class)->name('extra-charges.resolved');

      Route::get('/extra-charges/show/{unique_id}', ExtraChargesShowController::class)->name('extra-charges.show');

        Route::post(
            '/call-needed/{id}/complete',
            CallNeededCompleteController::class
        )->name('call-needed.complete');

      Route::post('/call-needed/store',CallNeededStoreController::class)->name('call-needed.store');

    Route::get('/call-needed/list', CallNeededListController::class)->name('call-needed.list');
Route::get('/call-needed/show/{id}', CallNeededShowController::class)->name('call-needed.show');
Route::post('/call-needed/clear/{id}', CallNeededClearController::class)->name('call-needed.clear');
Route::post(
    '/call-needed/update/{id}',
    CallNeededUpdateController::class
)->name('call-needed.update');

});
