<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Dashboard\IndexController;
use App\Http\Controllers\Admin\Dashboard\NotesStoreController;
use App\Http\Controllers\Admin\Dashboard\AmountUpdateController;
use App\Http\Controllers\Admin\Dashboard\PaymentStoreController;
use App\Http\Controllers\Admin\Dashboard\MarkUncollectibleController;


Route::prefix('dashboard')
->name('dashboard.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');

    Route::post('/notes-store/{unique_id}', NotesStoreController::class)->name('notes.store');

    Route::post('/amount-update/{unique_id}', AmountUpdateController::class)->name('amount.update');

    Route::post('/payment-store', PaymentStoreController::class)->name('paymentstore');

      Route::post('/extra-charges/uncollectible/{unique_id}', MarkUncollectibleController::class)->name('extra-charges.uncollectible');

});
