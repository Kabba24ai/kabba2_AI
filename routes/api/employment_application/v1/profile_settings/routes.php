<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\EmploymentApplication\V1\ProfileSettings\ShowController;

Route::group(['prefix' => 'profile-settings'], function () {

    Route::get('/', ShowController::class); // Get Profile Settings

});