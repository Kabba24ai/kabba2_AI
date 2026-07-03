<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder\ShowController;
use App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder\StoreController;
use App\Http\Controllers\Admin\WebsiteManagement\ThemeBuilder\CssVariablesController;

Route::prefix('theme-builder')->name('theme-builder.')->group(function () {

    // Redirect root → branding tab
    Route::get('/', IndexController::class)->name('index');

    // CSS custom properties endpoint (public — no auth needed for frontend use)
    Route::get('/css-variables.css', CssVariablesController::class)->name('css-variables')
         ->withoutMiddleware(['auth']);

    // Show a tab (GET)
    Route::get('/{tab}', ShowController::class)->name('show');

    // Save a group (POST)
    Route::post('/{group}/save', StoreController::class)->name('save');

});
