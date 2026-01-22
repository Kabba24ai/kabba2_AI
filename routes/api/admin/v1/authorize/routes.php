<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\Admin\V1\Authorize\PostController;
use App\Http\Controllers\Api\Admin\V1\Authorize\ScheduleController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Apply the default throttle middleware to limit requests to 3 per minute
Route::post('/authorize', PostController::class);
Route::post('/schedule', ScheduleController::class);
