<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Api\TimeTracker\V1\Auth\LoginController;
use App\Http\Controllers\Api\TimeTracker\V1\Auth\ListUsersController;
// use App\Http\Controllers\Api\TimeTracker\V1\Auth\LogoutController;

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
Route::middleware('throttle:20,1')->group(function () {
       Route::post('/login', LoginController::class);
});


Route::group(['prefix' => 'login-users'], function () {

        // LIST Users
        Route::get('/', ListUsersController::class);

});
