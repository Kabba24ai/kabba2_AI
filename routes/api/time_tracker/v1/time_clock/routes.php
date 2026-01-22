<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\GetActiveTimeEntryController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\GetTodayTimeEntryController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\ClockInController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\ClockOutController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\LunchStartController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\LunchEndController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\OtherStartController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\OtherEndController;



Route::group(['prefix' => 'time-clock'], function () {

       
        Route::get('/active', GetActiveTimeEntryController::class);
        Route::get('/today', GetTodayTimeEntryController::class);
        Route::post('/clock-in', ClockInController::class);
        Route::post('/clock-out', ClockOutController::class);

         Route::post('/lunch-start', LunchStartController::class);
        Route::post('/lunch-end', LunchEndController::class);
         Route::post('/other-start', OtherStartController::class);
        Route::post('/other-end', OtherEndController::class);

});
