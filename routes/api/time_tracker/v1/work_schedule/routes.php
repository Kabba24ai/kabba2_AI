<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\ListStoresController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\ListUsersController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\GetWorkScheduleController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\SaveWorkScheduleController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\UpdateWorkScheduleController;
use App\Http\Controllers\Api\TimeTracker\V1\WorkSchedule\GetStartWeekController;




Route::group(['prefix' => 'work-schedule'], function () {

   Route::get('/employees', ListUsersController::class);
   Route::get('/stores', ListStoresController::class);

   Route::get('/work-schedule', GetWorkScheduleController::class);

   Route::get('/start-date', GetStartWeekController::class);

   /*
   |--------------------------------------------------------------------------
   | Admin-Only Work Schedule Mutations
   |--------------------------------------------------------------------------
   */
   Route::middleware(['role.short:master_admin'])->group(function () {

       Route::post('/work-schedule/save', SaveWorkScheduleController::class);

       Route::post('/work-schedule/update', UpdateWorkScheduleController::class);

   });

});
