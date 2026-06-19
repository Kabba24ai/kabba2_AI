<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\ReleaseNotes\IndexController;
use App\Http\Controllers\Admin\ReleaseNotes\ShowController;

Route::prefix('release-notes')
    ->name('release-notes.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/{slug}', ShowController::class)->name('show');
    });
