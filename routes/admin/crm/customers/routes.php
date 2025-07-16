<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\Crm\Customers\IndexController;
use App\Http\Controllers\Admin\Crm\Customers\CreateController;
use App\Http\Controllers\Admin\Crm\Customers\EditController;
use App\Http\Controllers\Admin\Crm\Customers\StoreController;
use App\Http\Controllers\Admin\Crm\Customers\UpdateController;
use App\Http\Controllers\Admin\Crm\Customers\ViewUpdateController;
use App\Http\Controllers\Admin\Crm\Customers\DeleteController;
use App\Http\Controllers\Admin\Crm\Customers\CheckEmailController;
use App\Http\Controllers\Admin\Crm\Customers\ViewController;
use App\Http\Controllers\Admin\Crm\Customers\TaxDocumentDeleteController;
use App\Http\Controllers\Admin\Crm\Customers\TaxStatusUpdateController;
use App\Http\Controllers\Admin\Crm\Customers\LoginController;


Route::prefix('customers')
->name('customers.')
->group(function ($router) {

    Route::get('/', IndexController::class)->name('index');

    // Create
    Route::get('/create', CreateController::class)->name('create');
    Route::post('/create', StoreController::class);

    // View
    Route::get('/{unique_id}/view', ViewController::class)->name('view');
    Route::post('/{unique_id}/view', ViewUpdateController::class);

    // Edit
    Route::get('/{unique_id}/edit', EditController::class)->name('edit');
    Route::put('/{unique_id}/edit', UpdateController::class);

    Route::get('/check-email-unique', CheckEmailController::class)->name('check.email.unique');

    Route::post('/tax-status-update', TaxStatusUpdateController::class)->name('tax_status.update');


      // Create
    Route::get('/create', CreateController::class)->name('create');
   
    // Delete
    Route::delete('/{unique_id}', DeleteController::class)->name('delete');

    Route::delete('/tax-document/{unique_id}', TaxDocumentDeleteController::class)->name('tax-document.delete');
    Route::get('/{unique_id}/login', LoginController::class)->name('login');
});
