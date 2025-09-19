<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\Invoice\CreateController;


Route::prefix('invoice')
->name('invoice.')
->group(function ($router) {

    Route::get('/create', CreateController::class)->name('create');


});
