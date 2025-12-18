<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Orders\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Orders\EditController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BulkDeleteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ConfirmPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ChargeCreditCardController;
use App\Http\Controllers\Admin\OrderManagement\Orders\RefundPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateNoteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateProductScheduleController;
use App\Http\Controllers\Admin\OrderManagement\Orders\UpdateOrderAddressController;

use App\Http\Controllers\Admin\OrderManagement\Orders\ReceiptDownload;
use App\Http\Controllers\Admin\OrderManagement\Orders\SendReceiptEmailController;

use App\Http\Controllers\Admin\OrderManagement\Orders\AddToAccountPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\SendTermsAndConditionsController;

use App\Http\Controllers\Admin\OrderManagement\Orders\Notes\IndexController as NotesIndexController;
use App\Http\Controllers\Admin\OrderManagement\Orders\Notes\StoreController as NotesStoreController;
use App\Http\Controllers\Admin\OrderManagement\Orders\Notes\UpdateController as NotesUpdateController;
use App\Http\Controllers\Admin\OrderManagement\Orders\Notes\DeleteController as NotesDeleteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\Reorder\PostController as ReorderPostController;

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
        Route::put('/{unique_id}/charge-credit-card', ChargeCreditCardController::class)->name('charge-credit-card');
        Route::put('/{unique_id}/refund-payment', RefundPaymentController::class)->name('refund-payment');
        Route::put('/{unique_id}/add-to-account', AddToAccountPaymentController::class)->name('add-to-account');

        // Update Address
        Route::post('/{unique_id}/update-address', UpdateOrderAddressController::class)->name('update-address');

        // Delete
        Route::post('/bulk-delete', BulkDeleteController::class)->name('bulk-delete');

        // Reorder
        Route::post('/{unique_id}/reorder', ReorderPostController::class)->name('reorder');

        // Send Terms and Conditions
        Route::post('/{unique_id}/send-terms', SendTermsAndConditionsController::class)->name('send-terms');

        Route::get('/{unique_id}/receipt-download', ReceiptDownload::class)->name('receipt-download');
        Route::get('/{unique_id}/receipt-email', SendReceiptEmailController::class)->name('receipt-email');

        // Notes
        Route::prefix('notes')
            ->name('notes.')
            ->group(function () {
                Route::get('/{unique_id}', NotesIndexController::class)->name('index');
                Route::post('/{unique_id}', NotesStoreController::class)->name('store');
                Route::put('/{unique_id}/{note_unique_id}/update', NotesUpdateController::class)->name('update');
                Route::delete('/{unique_id}/{note_unique_id}', NotesDeleteController::class)->name('delete');
            });
    });
