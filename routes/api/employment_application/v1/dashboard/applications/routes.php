<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications\ListController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications\ShowController;
use App\Http\Controllers\Api\EmploymentApplication\V1\Dashboard\Applications\UpdateStatusController;


Route::group(['prefix' => 'applications'], function () {

Route::get('/list', ListController::class);

     Route::get('/full/{application}', ShowController::class);
     Route::post('/{application}/status/{status}', UpdateStatusController::class);

});
