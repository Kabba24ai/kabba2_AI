<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Auth\LoginController;

Route::group(['prefix' => 'auth'], function () {
        // list Stores
        Route::get('/login', LoginController::class);
});
