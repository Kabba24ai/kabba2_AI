<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Hrm\Users\IndexController;
use App\Http\Controllers\Admin\Hrm\Users\CreateUserController;
use App\Http\Controllers\Admin\Hrm\Users\ViewUserController;
use App\Http\Controllers\Admin\Hrm\Users\DeleteUserController;
use App\Http\Controllers\Admin\Hrm\Users\StoreUserController;
use App\Http\Controllers\Admin\Hrm\Users\CheckEmailController;
use App\Http\Controllers\Admin\Hrm\Users\EditUserController;
use App\Http\Controllers\Admin\Hrm\Users\UpdateUserController;



Route::prefix('users')
->name('users.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/create-user', CreateUserController::class)->name('create.user');
    Route::post('/create-user', StoreUserController::class);

    Route::get('/view-user/{unique_id}', ViewUserController::class)->name('view.user');

 
    Route::delete('/{unique_id}/delete-user/', DeleteUserController::class)->name('delete.user');


    Route::get('/edit-user/{unique_id}', EditUserController::class)->name('edit.user');
    Route::put('/edit-user/{unique_id}', UpdateUserController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

});
