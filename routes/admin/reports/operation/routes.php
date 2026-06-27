<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Reports\Operation\IndexController;

Route::prefix('operation')
    ->name('operation.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
