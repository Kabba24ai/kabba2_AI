<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Orders\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Orders\EditController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BulkDeleteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ConfirmPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateNoteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateProductScheduleController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateOrderAddressController;

Route::prefix('orders')
    ->name('orders.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');

        // // Create
        // Route::get('/create', CreateController::class)->name('create');
        // Route::post('/create', IndexController::class);

        // Edit
        Route::get('{unique_id}/edit', EditController::class)->name('edit');
        Route::put('/{unique_id}/update-note', UpdateNoteController::class)->name('update-note');
        Route::put('/{unique_id}/{product_unique_id}/update-product-schedule', UpdateProductScheduleController::class)->name('update-product-schedule');
        Route::put('/{unique_id}/confirm-payment', ConfirmPaymentController::class)->name('confirm-payment');
        Route::post('/{unique_id}/update-address', UpdateOrderAddressController::class)->name('update-address');

        // // Delete
        Route::post('/bulk-delete', BulkDeleteController::class)->name('bulk-delete');
    });
