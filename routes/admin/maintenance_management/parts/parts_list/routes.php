<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\CreateController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\ViewController;



use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\StoreController;


use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\EditController;
use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\UpdateController;

use App\Http\Controllers\Admin\MaintenanceManagement\Parts\PartsList\DeleteController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::prefix('parts-list')
->name('parts-list.')
->group(function ($router) {

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class)->name('store');


    Route::get('/view', ViewController::class)->name('view');

       Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/update', UpdateController::class)->name('update');


    Route::delete('/delete/{list}', DeleteController::class)->name('delete');

});
