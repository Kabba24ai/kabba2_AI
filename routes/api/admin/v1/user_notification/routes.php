<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\UserNotification\IndexController;

use App\Http\Controllers\Api\Admin\V1\UserNotification\UpdateStatusController;

Route::prefix('user-notification')->group(function () {

    // Get all notifications
   Route::get('/all', IndexController::class);

    // Update status using user_id + order_id
   Route::post('/update/status', UpdateStatusController::class);

});
