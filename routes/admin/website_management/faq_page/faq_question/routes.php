<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\WebsiteManagement\FaqPage\FaqQuestion\StoreController;
//use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\EditController;
//use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\UpdateController;
//use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\DeleteController;
//use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\FetchController;

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

    //Route::get('/edit/{unique_id}', EditController::class)->name('edit');
    //Route::put('/update/{unique_id}', UpdateController::class)->name('update');

    //Route::delete('/delete/{unique_id}', DeleteController::class)->name('delete');

    //Route::get('/fetch', FetchController::class)->name('fetch');
});
