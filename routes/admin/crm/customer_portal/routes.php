<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\CustomerPortal\IndexController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\CreateController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\EditController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\StoreController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\UpdateController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\DeleteController;



Route::prefix('customer_portal')
->name('customer_portal.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

     // Edit
     Route::get('/{unique_id}/edit', EditController::class)->name('edit');
     Route::put('/{unique_id}/edit', UpdateController::class);

      // Create
    Route::get('/create', CreateController::class)->name('create');
   
    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
});
