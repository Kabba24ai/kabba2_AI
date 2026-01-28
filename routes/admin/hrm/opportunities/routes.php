<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Hrm\Opportunities\LoginTokenController;



Route::prefix('opportunities')
->name('opportunities.')
->group(function ($router) {

    Route::get('/login-token', LoginTokenController::class)->name('login.token');

});
