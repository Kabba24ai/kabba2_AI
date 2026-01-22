<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Invoice\IndexController;

Route::prefix('invoice')
    ->name('invoice.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');

});
