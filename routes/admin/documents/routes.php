<?php

use App\Http\Controllers\Admin\Documents\PriceListFormController;
use App\Http\Controllers\Admin\Documents\PriceListGenerateController;
use Illuminate\Support\Facades\Route;

// Kabba Document Generation Framework — admin document tools
Route::prefix('documents')
    ->name('documents.')
    ->group(function () {
        Route::get('/price-list',          PriceListFormController::class)->name('price-list.form');
        Route::get('/price-list/generate', PriceListGenerateController::class)->name('price-list.generate');
    });
