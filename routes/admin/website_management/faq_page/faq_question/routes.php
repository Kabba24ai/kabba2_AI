<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion\StoreController;

use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion\UpdateController;

use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion\DeleteController;
use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion\BulkDeleteController;

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

Route::prefix('faq-question')
->name('faq-question.')
->group(function ($router) {

    Route::post('/store', StoreController::class)->name('store');

    Route::post('/{unique_id}/update', UpdateController::class)->name('update');

    Route::delete('/{unique_id}', DeleteController::class)->name('delete');


    Route::post('/bulk-delete', BulkDeleteController::class)
        ->name('bulk-delete');

});
