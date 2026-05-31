<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Dispatch\IndexController;

Route::prefix('dispatch')
->name('dispatch.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
});
