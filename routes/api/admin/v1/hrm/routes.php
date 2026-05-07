<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\Hrm\IndexController;
use App\Http\Controllers\Api\Admin\V1\Hrm\StoreController;
use App\Http\Controllers\Api\Admin\V1\Hrm\ShowController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::prefix('hrm')->group(function () {

    // List Users
    // Route::get('/', IndexController::class);

    // Create User
    Route::post('/create', StoreController::class);

    // Show Single User
//    Route::post('/show', ShowController::class);

});