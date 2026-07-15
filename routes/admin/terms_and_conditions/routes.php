<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\TermsAndConditions\IndexController;
use App\Http\Controllers\Admin\TermsAndConditions\CreateController;
use App\Http\Controllers\Admin\TermsAndConditions\StoreController;
use App\Http\Controllers\Admin\TermsAndConditions\EditController;
use App\Http\Controllers\Admin\TermsAndConditions\UpdateController;
use App\Http\Controllers\Admin\TermsAndConditions\DeleteController;

use App\Http\Controllers\Admin\TermsAndConditions\GlobalTermsController;
use App\Http\Controllers\Admin\TermsAndConditions\UpdateHeaderController;

Route::prefix('terms-and-conditions')
->name('terms-and-conditions.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Rental Agreement Header (lines shown atop the customer signing page)
    Route::post('/header', UpdateHeaderController::class)->name('header.update');

    // Create
    Route::get('/create/{termsId?}', CreateController::class)->name('create');
    Route::post('/create/{termsId?}', StoreController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');

    Route::get('/global', GlobalTermsController::class)->name('global');
});
