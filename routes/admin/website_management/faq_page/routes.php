<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\IndexController;
use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\StoreController;



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

Route::prefix('faq-page')
->name('faq-page.')
->group(function ($router) {

     Route::get('/', IndexController::class)->name('index');

    Route::post('/store', StoreController::class)->name('store');

    require base_path('routes/admin/website_management/faq_page/faq_question/routes.php');
});
