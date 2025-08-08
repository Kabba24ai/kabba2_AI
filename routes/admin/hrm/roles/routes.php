<?php

use Illuminate\Support\Facades\Route;



use App\Http\Controllers\Admin\Hrm\Roles\ManageRoleController;
use App\Http\Controllers\Admin\Hrm\Roles\CreateRoleController;
use App\Http\Controllers\Admin\Hrm\Roles\StoreRoleController;
use App\Http\Controllers\Admin\Hrm\Roles\EditRoleController;
use App\Http\Controllers\Admin\Hrm\Roles\UpdateRoleController;
use App\Http\Controllers\Admin\Hrm\Roles\DeleteRoleController;



Route::prefix('roles')
->name('roles.')
->group(function ($router) {


     Route::get('/manage-role', ManageRoleController::class)->name('manage.role');

     Route::get('/create-role', CreateRoleController::class)->name('create.role');
     Route::post('/create-role', StoreRoleController::class);    

     Route::get('/{unique_id}/edit-role', EditRoleController::class)->name('edit.role');
     Route::put('/{unique_id}/edit-role', UpdateRoleController::class);

     Route::delete('/{unique_id}/delete', DeleteRoleController::class)->name('delete');

     
});
