<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Admin\V1\Tasks\IndexController;
use App\Http\Controllers\Api\Admin\V1\Tasks\ShowController;
use App\Http\Controllers\Api\Admin\V1\Tasks\StoreController;
use App\Http\Controllers\Api\Admin\V1\Tasks\UpdateController;
use App\Http\Controllers\Api\Admin\V1\Tasks\StoreCommentController;
use App\Http\Controllers\Api\Admin\V1\Tasks\StartController;
use App\Http\Controllers\Api\Admin\V1\Tasks\CompleteController;
use App\Http\Controllers\Api\Admin\V1\Tasks\CancelController;
use App\Http\Controllers\Api\Admin\V1\Tasks\DashboardCountsController;

Route::prefix('tasks')->group(function () {
    Route::get('/dashboard-counts',    DashboardCountsController::class);
    Route::get('/',                    IndexController::class);
    Route::post('/',                   StoreController::class);
    Route::get('/{task}',              ShowController::class);
    Route::patch('/{task}',            UpdateController::class);
    Route::post('/{task}/comments',    StoreCommentController::class);
    Route::post('/{task}/start',       StartController::class);
    Route::post('/{task}/complete',    CompleteController::class);
    Route::post('/{task}/cancel',      CancelController::class);
});
