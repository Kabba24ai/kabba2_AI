<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Schedules\IndexController;

Route::prefix('schedules')
->name('schedules.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');
});
