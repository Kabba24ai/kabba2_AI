<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\Invoice\CreateController;
use App\Http\Controllers\Admin\Crm\Customers\Invoice\IndexController;

Route::prefix('invoice')
->name('invoice.')
->group(function ($router) {

    Route::get('/{unique_id}',  IndexController::class)->name('index');


    Route::get('{unique_id}/create', CreateController::class)->name('create');


});
