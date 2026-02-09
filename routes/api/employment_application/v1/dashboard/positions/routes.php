<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions\ListController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions\UpdateStatusController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions\StoreController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions\UpdateController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Positions\DeleteController;


Route::group(['prefix' => 'positions'], function () {

    Route::get('/list', ListController::class);
    Route::post('/{position}/status', UpdateStatusController::class);

    Route::post('/store', StoreController::class);
    Route::post('/{position}', UpdateController::class);

    Route::get('/{position}/delete', DeleteController::class);

    
});
