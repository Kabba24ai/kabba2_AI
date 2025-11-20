<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\HomePage\IndexController;


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

Route::prefix('home-page')
->name('home-page.')
->group(function ($router) {

     Route::get('/', IndexController::class)->name('index');

});
