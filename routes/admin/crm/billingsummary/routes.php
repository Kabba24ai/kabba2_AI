<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\BillingSummary\IndexController;
use App\Http\Controllers\Admin\Crm\BillingSummary\ViewController;



Route::prefix('billingsummary')
->name('billingsummary.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/{unique_id}/view', ViewController::class)->name('view');

});
