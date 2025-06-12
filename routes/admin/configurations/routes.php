<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Configurations\IndexController;
use App\Http\Controllers\Admin\Configurations\UpdateController;

Route::prefix('configurations')
->name('configurations.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
    Route::post('/', UpdateController::class);

});
