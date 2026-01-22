<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Funnels\IndexController;
use App\Http\Controllers\Admin\Crm\Funnels\SaveController;

Route::prefix('funnels')
->name('funnels.')
->group(function () {
    Route::get('/', IndexController::class)->name('index');
    Route::post('/', SaveController::class)->name('save');
});
