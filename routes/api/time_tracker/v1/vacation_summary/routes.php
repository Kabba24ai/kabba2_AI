<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\VacationSummary\VacationSummaryController;
use App\Http\Controllers\Api\TimeTracker\V1\VacationSummary\VacationRequestHourController;
use App\Http\Controllers\Api\TimeTracker\V1\VacationSummary\VacationRequestStoreController;
use App\Http\Controllers\Api\TimeTracker\V1\VacationSummary\MyVacationRequestController;



Route::group(['prefix' => 'vacation-summary'], function () {

        // LIST employees
        Route::get('/get', VacationSummaryController::class);

        Route::get('/get/vacation-request-hour', VacationRequestHourController::class);
         Route::post('/vacation-request/store', VacationRequestStoreController::class);
         Route::get('/my-vacation-request', MyVacationRequestController::class);

});
