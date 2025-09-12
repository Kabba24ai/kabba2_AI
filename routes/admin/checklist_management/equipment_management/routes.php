<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\IndexController;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\ChecklistQuestionsController ;
use App\Http\Controllers\Admin\ChecklistManagement\EquipmentManagement\StoreController;



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

Route::prefix('equipment-management')
->name('equipment-management.')
->group(function ($router) {

     Route::get('/', IndexController::class)->name('index');

    Route::get('/{equipment}', IndexController::class)->name('show');


    Route::post('/get-checklist-questions', ChecklistQuestionsController::class)->name('get-checklist-questions');

    Route::post('/store', StoreController::class)->name('store');
});
