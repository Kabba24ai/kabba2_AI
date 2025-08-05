<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Roles\IndexController;
use App\Http\Controllers\Admin\Roles\CreateUserController;
use App\Http\Controllers\Admin\Roles\ViewUserController;


Route::prefix('roles')
->name('roles.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/create-user', CreateUserController::class)->name('create.user');
   
    Route::get('/view-user', ViewUserController::class)->name('view.user');
});
