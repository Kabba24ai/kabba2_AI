<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\CustomerChecklists\IndexController;

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

Route::group(['prefix' => 'customer-checklists'], function () {
    Route::post('/question-answers', IndexController::class);
});
