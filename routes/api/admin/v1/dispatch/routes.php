<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\V1\Dispatch\IndexController;
use App\Http\Controllers\Api\Admin\V1\Dispatch\UpdateStatusController;

Route::group(['prefix' => 'dispatch'], function () {
    Route::post('/', IndexController::class);
    Route::post('/update-status', UpdateStatusController::class);
});
