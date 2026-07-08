<?php

use Illuminate\Support\Facades\Route;

// Controllers
use App\Http\Controllers\Admin\OrderManagement\Orders\IndexController;
use App\Http\Controllers\Admin\OrderManagement\Orders\EditController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BulkDeleteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ConfirmPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ChargeCreditCardController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ReceivePaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\RefundPaymentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\VoidPaymentController;
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
use App\Http\Controllers\Admin\OrderManagement\Orders\AssignEquipmentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\RemoveEquipmentController;
use App\Http\Controllers\Admin\OrderManagement\Orders\DeclinedOrdersController;
use App\Http\Controllers\Admin\OrderManagement\Orders\TransactionDetailsController;

use App\Http\Controllers\Admin\OrderManagement\Orders\UpdatePoidController;

use App\Http\Controllers\Admin\OrderManagement\Orders\RepairDeletedOrdersController;
use App\Http\Controllers\Admin\OrderManagement\Orders\AlertChargeController;
use App\Http\Controllers\Admin\OrderManagement\Orders\Extension\StoreController as ExtensionStoreController;
use App\Http\Controllers\Admin\OrderManagement\Orders\ResendPodPaymentLinkController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine\ResolveController as BEResolveController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine\UncollectibleController as BEUncollectibleController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine\NoteController as BENoteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine\AdjustController as BEAdjustController;
use App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine\DeleteController as BEDeleteController;
use App\Http\Controllers\Admin\OrderManagement\Orders\CustomerCredit\ApplyController as CustomerCreditApplyController;
use App\Http\Controllers\Admin\OrderManagement\Orders\CustomerCredit\RemoveController as CustomerCreditRemoveController;


Route::prefix('orders')
    ->name('orders.')
    ->group(function () {
        Route::get('/', IndexController::class)->name('index');
        Route::get('/declined', DeclinedOrdersController::class)->name('declined');
        Route::get('/transaction-details/{transaction_id}', TransactionDetailsController::class)->name('transaction-details');

        // // Create
        // Route::get('/create', CreateController::class)->name('create');
        // Route::post('/create', IndexController::class);

        // Edit
        Route::get('{unique_id}/edit', EditController::class)->name('edit');
        Route::put('/{unique_id}/update-note', UpdateNoteController::class)->name('update-note');
        Route::put('/{unique_id}/{product_unique_id}/update-product-schedule', UpdateProductScheduleController::class)->name('update-product-schedule');
        Route::post('/assign-equipment', AssignEquipmentController::class)->name('assign-equipment');
        Route::delete('/remove-equipment', RemoveEquipmentController::class)->name('remove-equipment');
        Route::put('/{unique_id}/confirm-payment', ConfirmPaymentController::class)->name('confirm-payment');
        Route::put('/{unique_id}/charge-credit-card', ChargeCreditCardController::class)->name('charge-credit-card');
        Route::put('/{unique_id}/receive-payment', ReceivePaymentController::class)->name('receive-payment');
        Route::put('/{unique_id}/refund-payment', RefundPaymentController::class)->name('refund-payment');
        Route::put('/{unique_id}/void-payment', VoidPaymentController::class)->name('void-payment');
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


        Route::post('/update-po', UpdatePoidController::class)->name('update-po-id');

        Route::post('/{unique_id}/alert-charge', AlertChargeController::class)->name('alert-charge');

        // Extension Charges
        Route::post('/{unique_id}/extension/store', ExtensionStoreController::class)->name('extension.store');

        // POD Payment Link — manual resend
        Route::post('/{unique_id}/resend-pod-payment-link', ResendPodPaymentLinkController::class)->name('resend-pod-payment-link');

        // Customer Credit — Phase 3.2, Order Entry Integration. Manual only.
        Route::prefix('{unique_id}/customer-credit')
            ->name('customer-credit.')
            ->group(function () {
                Route::post('/apply', CustomerCreditApplyController::class)->name('apply')->middleware('permission:customer_credit.redeem');
                Route::post('/remove', CustomerCreditRemoveController::class)->name('remove')->middleware('permission:customer_credit.grant');
            });

        // Billing Engine — fuel charge actions (operate on billing_charges.unique_id, not order_product_id)
        Route::prefix('billing-charges')
            ->name('billing-charges.')
            ->group(function () {
                Route::post('/{chargeUniqueId}/resolve',       BEResolveController::class)->name('resolve');
                Route::post('/{chargeUniqueId}/uncollectible', BEUncollectibleController::class)->name('uncollectible');
                Route::post('/{chargeUniqueId}/note',          BENoteController::class)->name('note');
                Route::post('/{chargeUniqueId}/adjust',        BEAdjustController::class)->name('adjust');
                Route::post('/{chargeUniqueId}/delete',        BEDeleteController::class)->name('delete');
            });

        // Route::get('repair-deleted-orders', RepairDeletedOrdersController::class)->name('repair-deleted-orders');


    });
