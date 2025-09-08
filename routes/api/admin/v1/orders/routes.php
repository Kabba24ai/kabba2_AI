<?php

use App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists\RemoveController as CustomerChecklistsRemoveController;
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
use App\Http\Controllers\Api\Admin\V1\Orders\Schedules\IndexController as SchedulesIndexController;
use App\Http\Controllers\Api\Admin\V1\Orders\Schedules\UpdateController as SchedulesUpdateController;
use App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists\SaveDeliveryController;
use App\Http\Controllers\Api\Admin\V1\Orders\CustomerChecklists\SaveReturnController;
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

    Route::group(['prefix' => 'schedules'], function () {
        Route::post('/', SchedulesIndexController::class);
        Route::post('/update', SchedulesUpdateController::class);
    });

    Route::group(['prefix' => 'notes'], function () {
        Route::post('/create', StoreController::class);
        Route::post('/update', UpdateController::class);
        Route::post('/remove', RemoveController::class);
    });

    Route::group(['prefix' => 'customer-checklists'], function () {
        Route::post('/remove', CustomerChecklistsRemoveController::class);
        Route::post('/save-delivery', SaveDeliveryController::class);
        Route::post('/save-return', SaveReturnController::class);
    });
});
