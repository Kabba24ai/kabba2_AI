<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\CustomerPortal\IndexController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\CreateController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\EditController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\StoreController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\UpdateController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\DeleteController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\CheckEmailController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\ViewController;
use App\Http\Controllers\Admin\Crm\CustomerPortal\TaxDocumentDeleteController;


Route::prefix('customer_portal')
->name('customer_portal.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

    // View
    Route::get('/{unique_id}/view', ViewController::class)->name('view');

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

      // Create
    Route::get('/create', CreateController::class)->name('create');
   
    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');

    Route::delete('/tax-document/{unique_id}', TaxDocumentDeleteController::class)->name('tax-document.delete');
});
