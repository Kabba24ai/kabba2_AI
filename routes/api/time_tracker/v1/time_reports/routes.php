<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\TimeReports\ListTimeReportsController;



Route::group(['prefix' => 'time-reports'], function () {

    Route::get('/get', ListTimeReportsController::class);


});
