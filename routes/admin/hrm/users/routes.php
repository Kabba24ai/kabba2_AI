<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Hrm\Users\IndexController;
use App\Http\Controllers\Admin\Hrm\Users\CreateController;
use App\Http\Controllers\Admin\Hrm\Users\ViewController;
use App\Http\Controllers\Admin\Hrm\Users\DeleteController;
use App\Http\Controllers\Admin\Hrm\Users\StoreController;
use App\Http\Controllers\Admin\Hrm\Users\CheckEmailController;
use App\Http\Controllers\Admin\Hrm\Users\EditController;
use App\Http\Controllers\Admin\Hrm\Users\UpdateController;



Route::prefix('users')
->name('users.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

    Route::get('/view/{unique_id}', ViewController::class)->name('view');

 
    Route::delete('/{unique_id}/delete/', DeleteController::class)->name('delete');


    Route::get('/edit/{unique_id}', EditController::class)->name('edit');
    Route::put('/edit/{unique_id}', UpdateController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

});
