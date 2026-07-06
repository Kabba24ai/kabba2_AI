<?php

use App\Http\Controllers\Admin\Documents\PriceListFormController;
use App\Http\Controllers\Admin\Documents\PriceListGenerateController;
use App\Http\Controllers\Admin\Documents\Presets;
use App\Http\Controllers\Admin\Documents\Text;
use Illuminate\Support\Facades\Route;

// Kabba Document Generation Framework — admin document tools
Route::prefix('documents')
    ->name('documents.')
    ->group(function () {
        Route::get('/price-list',          PriceListFormController::class)->name('price-list.form');
        Route::get('/price-list/generate', PriceListGenerateController::class)->name('price-list.generate');

        // Document Text tab — Price List title/value message/disclaimer
        Route::get('/price-list/document-text',  Text\ShowController::class)->name('price-list.text');
        Route::post('/price-list/document-text', Text\SaveController::class)->name('price-list.text.save');

        // Industry Presets — admin-managed selection shortcuts for the price list
        Route::prefix('price-list-presets')
            ->name('presets.')
            ->group(function () {
                Route::get('/',                 Presets\IndexController::class)->name('index');
                Route::get('/create',           Presets\CreateController::class)->name('create');
                Route::post('/',                Presets\StoreController::class)->name('store');
                Route::get('/{preset}/edit',    Presets\EditController::class)->name('edit');
                Route::put('/{preset}',         Presets\UpdateController::class)->name('update');
                Route::patch('/{preset}/toggle', Presets\ToggleController::class)->name('toggle');
                Route::delete('/{preset}',      Presets\DestroyController::class)->name('destroy');
            });
    });
