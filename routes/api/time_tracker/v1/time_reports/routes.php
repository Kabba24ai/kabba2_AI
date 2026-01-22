<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\TimeReports\ListTimeReportsController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeReports\PayPeriodsReportsController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeReports\ExportTimeReportsController;




Route::group(['prefix' => 'time-reports'], function () {

    Route::get('/get', ListTimeReportsController::class);

    Route::get('/pay-periods', PayPeriodsReportsController::class);

    Route::get('/export', ExportTimeReportsController::class);

});
