<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Reports\SalesTax\IndexController;



Route::prefix('sales-tax')
->name('sales-tax.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

});
