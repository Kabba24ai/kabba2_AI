<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\Orders\IndexController;
use App\Http\Controllers\Api\Admin\V1\Orders\ShowController;
use App\Http\Controllers\Api\Admin\V1\Orders\UploadMediaController;
use App\Http\Controllers\Api\Admin\V1\Orders\RemoveMediaController;
use App\Http\Controllers\Api\Admin\V1\Orders\Notes\StoreController;
use App\Http\Controllers\Api\Admin\V1\Orders\Notes\UpdateController;
use App\Http\Controllers\Api\Admin\V1\Orders\Notes\RemoveController;
use App\Http\Controllers\Api\Admin\V1\Orders\UpdateAddressController;

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

Route::group(['prefix' => 'orders'], function () {
    Route::post('/', IndexController::class);
    Route::post('/details', ShowController::class);
    Route::post('/upload-media', UploadMediaController::class);
    Route::post('/remove-media', RemoveMediaController::class);
    Route::post('/update-address', UpdateAddressController::class);

    Route::group(['prefix' => 'notes'], function () {
        Route::post('/create', StoreController::class);
        Route::post('/update', UpdateController::class);
        Route::post('/remove', RemoveController::class);
    });
});
