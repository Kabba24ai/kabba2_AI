<?php

use Illuminate\Support\Facades\Route;



use App\Http\Controllers\Admin\Hrm\Roles\ManageController;
use App\Http\Controllers\Admin\Hrm\Roles\CreateController;
use App\Http\Controllers\Admin\Hrm\Roles\StoreController;
use App\Http\Controllers\Admin\Hrm\Roles\EditController;
use App\Http\Controllers\Admin\Hrm\Roles\UpdateController;
use App\Http\Controllers\Admin\Hrm\Roles\DeleteController;



Route::prefix('roles')
->name('roles.')
->group(function ($router) {


     Route::get('/manage', ManageController::class)->name('manage');

     Route::get('/create', CreateController::class)->name('create');
     Route::post('/create', StoreController::class);    

     Route::get('/{unique_id}/edit', EditController::class)->name('edit');
     Route::put('/{unique_id}/edit', UpdateController::class);

     Route::delete('/{unique_id}/delete', DeleteController::class)->name('delete');

     
});
