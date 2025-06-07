<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\TermsAndCondition\Terms\IndexController;
use App\Http\Controllers\Admin\TermsAndCondition\Terms\CreateController;
use App\Http\Controllers\Admin\TermsAndCondition\Terms\StoreController;
use App\Http\Controllers\Admin\TermsAndCondition\Terms\EditController;
use App\Http\Controllers\Admin\TermsAndCondition\Terms\UpdateController;
use App\Http\Controllers\Admin\TermsAndCondition\Terms\DeleteController;

Route::prefix('terms')
->name('terms.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create/{termsId?}', CreateController::class)->name('create');
    Route::post('/create/{termsId?}', StoreController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');
});
