<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\ChecklistManagement\RentalReady\Templates\IndexController;


Route::prefix('templates')
->name('templates.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

});
