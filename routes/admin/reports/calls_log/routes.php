<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\Reports\CallsLog\IndexController;

Route::prefix('calls-log')
    ->name('calls-log.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
    });
