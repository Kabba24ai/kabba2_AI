<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\Attendance\GetAttendanceController;

use App\Http\Controllers\Api\TimeTracker\V1\Attendance\GetAchievementGoalsController;
use App\Http\Controllers\Api\TimeTracker\V1\Attendance\StoreAchievementGoalController;
use App\Http\Controllers\Api\TimeTracker\V1\Attendance\UpdateAchievementGoalController;



Route::get('/attendance', GetAttendanceController::class);

Route::get('/achievement-goals', GetAchievementGoalsController::class);
Route::post('/achievement-goals/store', StoreAchievementGoalController::class);
Route::put('/achievement-goals/update/{goal}', UpdateAchievementGoalController::class);
