<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Api\Admin\V1\Equipment\IndexController;
use App\Http\Controllers\Api\Admin\V1\EquipmentRentalReady\IndexController as RentalReadyIndexController;

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

Route::group(['prefix' => 'equipment'], function () {
    Route::post('/', IndexController::class);
});

// Dedicated list endpoint for the admin app's Equipment Rental Ready screen.
// Mirrors the web rental-ready screen's filters, data, and sort order
// (see admin.checklist-management.equipment-management.index) without
// altering the shared /equipment endpoint above.
Route::group(['prefix' => 'equipment-rental-ready'], function () {
    Route::post('/', RentalReadyIndexController::class);
});
