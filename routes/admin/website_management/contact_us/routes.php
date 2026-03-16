<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\ContactUs\IndexController;

use App\Http\Controllers\Admin\WebsiteManagement\ContactUs\SaveController;
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

Route::prefix('contact-us')
->name('contact-us.')
->group(function ($router) {

     Route::get('/', IndexController::class)->name('index');

        // Save  settings
        Route::post('/update', SaveController::class)
            ->name('update');

});
