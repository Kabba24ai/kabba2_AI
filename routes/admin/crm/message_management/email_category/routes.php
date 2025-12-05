<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory\StoreController;

use App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory\IndexController;
use App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory\ShowController;
use App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory\UpdateController;

use App\Http\Controllers\Admin\Crm\MessageManagement\EmailCategory\DeleteController;


Route::prefix('email-category')
->name('email-category.')
->group(function ($router) {

     Route::get('/', IndexController::class)->name('index');

    Route::post('/store', StoreController::class)->name('store');

  // SHOW one category (for edit modal)
        Route::get('/{unique_id}', ShowController::class)->name('show');

        // UPDATE existing category
        Route::put('/{unique_id}/update', UpdateController::class)->name('update');

Route::delete('/{unique_id}', DeleteController::class)->name('delete');

});
