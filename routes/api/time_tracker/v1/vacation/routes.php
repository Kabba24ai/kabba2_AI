<?php

use Illuminate\Support\Facades\Route;

// Controllers

use App\Http\Controllers\Api\TimeTracker\V1\Vacation\ListVacationBalancesController;

use App\Http\Controllers\Api\TimeTracker\V1\Vacation\AdminVacationRequestController;
use App\Http\Controllers\Api\TimeTracker\V1\Vacation\ApproveVacationRequestController;
use App\Http\Controllers\Api\TimeTracker\V1\Vacation\DenyVacationRequestController;

use App\Http\Controllers\Api\TimeTracker\V1\Vacation\UpdateUserVacationController;



Route::group(['prefix' => 'vacation'], function () {

    Route::get('/get-vacation-balances', ListVacationBalancesController::class);

    Route::get('/all/vacation-requests', AdminVacationRequestController::class);

    Route::post('/vacation-requests/{id}/approve', ApproveVacationRequestController::class);

    Route::post('/vacation-requests/{id}/deny', DenyVacationRequestController::class);

    Route::post('/update-user-vacation/{employeeId}', UpdateUserVacationController::class);

    



});
