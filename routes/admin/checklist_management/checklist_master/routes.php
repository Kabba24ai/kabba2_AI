<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\IndexController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\CreateController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\StoreController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\EditController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\AssignChecklistController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\UpdateController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\DeleteController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\FetchController;
use App\Http\Controllers\Admin\ChecklistManagement\ChecklistMaster\CopyController;

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

Route::prefix('checklist-master')
->name('checklist-master.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/create', CreateController::class)->name('create');
    Route::post('/store', StoreController::class)->name('store');

    Route::get('/edit/{unique_id}', EditController::class)->name('edit');

    Route::get('/assign-checklist/{unique_id}', AssignChecklistController::class)->name('assign-checklist');

    Route::put('/update/{unique_id}', UpdateController::class)->name('update');

    Route::post('/{id}/copy', CopyController::class)->name('copy');



    Route::delete('/delete/{unique_id}', DeleteController::class)->name('delete');

    Route::get('/fetch', FetchController::class)->name('fetch');
});
