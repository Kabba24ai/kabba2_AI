<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\Customers\IndexController;
use App\Http\Controllers\Api\Admin\V1\Customers\StoreController;
use App\Http\Controllers\Api\Admin\V1\Customers\Tags\IndexController as TagsIndexController;
use App\Http\Controllers\Api\Admin\V1\Customers\Tags\StoreController as TagsStoreController;
use App\Http\Controllers\Api\Admin\V1\Customers\Tags\AssignController as TagsAssignController;
use App\Http\Controllers\Api\Admin\V1\Customers\Cards\IndexController as CardsIndexController;
use App\Http\Controllers\Api\Admin\V1\Customers\Notes\IndexController as NotesIndexController;
use App\Http\Controllers\Api\Admin\V1\Customers\Notes\StoreController as NotesStoreController;
use App\Http\Controllers\Api\Admin\V1\Customers\Notes\UpdateController as NotesUpdateController;
use App\Http\Controllers\Api\Admin\V1\Customers\Notes\RemoveController as NotesRemoveController;

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

Route::group(['prefix' => 'customers'], function () {
    Route::post('/', IndexController::class);
    Route::post('/store', StoreController::class);

    Route::group(['prefix' => 'tags'], function () {
        Route::post('/', TagsIndexController::class);
        Route::post('/store', TagsStoreController::class);
        Route::post('/assign', TagsAssignController::class);
    });

    Route::group(['prefix' => 'cards'], function () {
        Route::post('/', CardsIndexController::class);
    });

    Route::group(['prefix' => 'notes'], function () {
        Route::post('/', NotesIndexController::class);
        Route::post('/create', NotesStoreController::class);
        Route::post('/update', NotesUpdateController::class);
        Route::post('/remove', NotesRemoveController::class);
    });
});
