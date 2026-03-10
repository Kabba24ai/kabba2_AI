<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\ListStoresController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\ListUsersController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\GetWorkScheduleController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\SaveWorkScheduleController;


Route::group(['prefix' => 'work-schedule'], function () {

   Route::get('/employees', ListUsersController::class);
   Route::get('/stores', ListStoresController::class);

   Route::get('/work-schedule', GetWorkScheduleController::class);

   Route::post('/work-schedule/save', SaveWorkScheduleController::class);


});
