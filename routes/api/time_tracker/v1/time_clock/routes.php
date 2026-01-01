<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\GetActiveTimeEntryController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\GetTodayTimeEntryController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\ClockInController;
use App\Http\Controllers\Api\TimeTracker\V1\TimeClock\ClockOutController;


Route::group(['prefix' => 'time-clock'], function () {

       
        Route::get('/active', GetActiveTimeEntryController::class);
        Route::get('/today', GetTodayTimeEntryController::class);
        Route::post('/clock-in', ClockInController::class);
        Route::post('/clock-out', ClockOutController::class);

});
