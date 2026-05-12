<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\Clients\PostController;
use App\Http\Controllers\Api\Admin\V1\Clients\StoreApplicationCodeController;
use App\Http\Controllers\Api\Admin\V1\Clients\GetApplicationCodeController;



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

Route::group(['prefix' => 'clients'], function () {
    Route::post('/', PostController::class);

    Route::post('/client/application-code', StoreApplicationCodeController::class);

     Route::get(
        '/get/application-code',
        GetApplicationCodeController::class
    );
});
