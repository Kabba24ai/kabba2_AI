<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Dashboard\IndexController;

Route::prefix('dashboard')
->name('dashboard.')
->group(function ($router) {
    Route::get('/', IndexController::class)->name('index');
});
