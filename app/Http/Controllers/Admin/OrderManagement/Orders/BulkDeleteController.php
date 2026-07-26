<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Requests
use App\Http\Requests\Admin\OrderManagement\Orders\BulkDeleteRequest;
use App\Http\Requests\Admin\OrderManagement\Orders\DeleteExtensionTransactionRequest;

// Models
use App\Models\Orders\Order;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;

// Helpers
use App\Helpers\CustomHelper;

// Services
use App\Services\ExtensionTransactionService;

class BulkDeleteController extends Controller
{
    /**
     * Bulk Delete Orders
     */
    public function __invoke(BulkDeleteRequest $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Request Started
        |--------------------------------------------------------------------------
        */

        // Log::info('Bulk Order Delete Started', [

        //     'requested_by' => auth()->user()?->full_name,

        //     'requested_user_id' => auth()->id(),

        //     'payload' => $request->all(),

        // ]);

        $uniqueIds = $request->validated()['unique_ids'] ?? [];

        if (empty($uniqueIds)) {

            // Log::warning('Bulk Delete Failed - No Orders Selected');

            return response()->json([
                'message' => 'No orders selected for deletion.'
            ], 422);
        }

        // Optional administrative disposition for deleting a PAID extension
        // child (single-delete modal sends these fields). Validated up front
        // so a bad employee code fails before anything is deleted.
        $extensionDisposition = null;
        if ($request->filled('processed_by')) {
            $extensionDisposition = ExtensionTransactionService::buildDisposition(
                app(DeleteExtensionTransactionRequest::class)->validated()
            );
        }

        $blockedExtensions = [];

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | Fetch Orders
            |--------------------------------------------------------------------------
            */

            $orders = Order::whereIn(
                'unique_id',
                $uniqueIds
            )->get();

            // Log::info('Orders Loaded For Bulk Delete', [

            //     'total_orders' => $orders->count(),

            //     'unique_ids' => $uniqueIds,

            // ]);

            foreach ($orders as $order) {

                // Log::info('Processing Order Delete', [

                //     'order_id' => $order->id,

                //     'order_unique_id' => $order->unique_id,

                // ]);

                /*
                |--------------------------------------------------------------------------
                | Extension Transaction (coordinated deletion)
                |--------------------------------------------------------------------------
                | A verified extension child (has a linked Rental Extension
                | BillingCharge) and that charge are one transaction: deleting
                | the child must also remove the charge from the parent order.
                | Paid extensions without a Kabba refund/void are never
                | silently deleted — they need an administrative disposition.
                */

                $extensionCharge = ExtensionTransactionService::chargeForChild($order);

                if ($extensionCharge) {

                    // Consistency Initiative Phase 10: EVERY extension delete
                    // requires a verified-employee disposition, so a bulk
                    // selection can only remove an extension when the
                    // disposition fields were supplied. Without one, the
                    // extension is skipped and reported — it must be deleted
                    // through the single-delete flow (which collects the PIN).
                    if (!$extensionDisposition) {
                        $blockedExtensions[] = $order->order_number;
                        continue;
                    }

                    ExtensionTransactionService::delete(
                        $order,
                        $extensionCharge,
                        ExtensionTransactionService::ENTRY_CHILD_ORDER,
                        auth()->user(),
                        $extensionDisposition,
                    );

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Customer Account Entries
                |--------------------------------------------------------------------------
                */

                $transactions = CustomerAccount::where(
                    'order_id',
                    $order->id
                )->get();

                // Log::info('Customer Transactions Found', [

                //     'order_id' => $order->id,

                //     'transactions_count' => $transactions->count(),

                // ]);

                foreach ($transactions as $transaction) {

                    // Log::info('Processing Transaction Delete', [

                    //     'transaction_id' => $transaction->id,

                    //     'customer_id' => $transaction->customer_id,

                    //     'invoice_id' => $transaction->invoice_id,

                    //     'invoice_item_id' => $transaction->invoice_item_id,

                    // ]);

                    $customerId = $transaction->customer_id;

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Log
                    |--------------------------------------------------------------------------
                    */

                    $now = now()->format('M d, Y h:i A');

                    $userName = auth()->user()?->full_name ?? 'System';

                    $logEntry = [

                        'id' => uniqid('log_'),

                        'action' => 'transaction_deleted',

                        'performed_by' => [
                            'id' => auth()->id(),
                            'name' => $userName,
                        ],

                        'performed_at' => now()->toDateTimeString(),

                        'note' => "{$userName} deleted transaction on {$now}",

                        'snapshot' => [

                            'amount' => $transaction->amount,

                            'payment_type' => $transaction->payment_type,

                            'notes' => $transaction->notes,

                        ],
                    ];

                    $logs = $transaction->customer_action_log ?? [];

                    $logs[] = $logEntry;

                    $logs = array_slice($logs, -20);

                    $transaction->customer_action_log = $logs;

                    $transaction->save();

                    // Log::info('Transaction Log Saved', [

                    //     'transaction_id' => $transaction->id,

                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Invoice Cleanup
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $transaction->invoice_id &&
                        $transaction->invoice_item_id
                    ) {

                        // Log::info('Invoice Cleanup Started', [

                        //     'invoice_id' => $transaction->invoice_id,

                        //     'invoice_item_id' => $transaction->invoice_item_id,

                        // ]);

                        $invoice = Invoice::find(
                            $transaction->invoice_id
                        );

                        $invoiceItem = InvoiceItem::find(
                            $transaction->invoice_item_id
                        );

                        if ($invoiceItem) {

                            $invoiceItem->delete();

                            // Log::info('Invoice Item Deleted', [

                            //     'invoice_item_id' => $transaction->invoice_item_id,

                            // ]);
                        }

                        if ($invoice) {

                            CustomHelper::updateInvoiceSummary(
                                $invoice
                            );

                            // Log::info('Invoice Summary Updated', [

                            //     'invoice_id' => $invoice->id,

                            // ]);
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Reverse Balance
                    |--------------------------------------------------------------------------
                    */

                    CustomHelper::reverseTransactionEffect(
                        $transaction
                    );

                    // Log::info('Transaction Effect Reversed', [

                    //     'transaction_id' => $transaction->id,

                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Delete Transaction
                    |--------------------------------------------------------------------------
                    */

                    $transaction->delete();

                    // Log::info('Transaction Deleted', [

                    //     'transaction_id' => $transaction->id,

                    // ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Fix Running Balance
                    |--------------------------------------------------------------------------
                    */

                    CustomHelper::fixTheRunningBalance(
                        $customerId
                    );

                    // Log::info('Customer Running Balance Fixed', [

                    //     'customer_id' => $customerId,

                    // ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Delete Order
                |--------------------------------------------------------------------------
                */

                $order->delete();

                // Log::info('Order Deleted Successfully', [

                //     'order_id' => $order->id,

                //     'order_unique_id' => $order->unique_id,

                // ]);
            }

            DB::commit();

            // Log::info('Bulk Order Delete Completed Successfully', [

            //     'total_deleted_orders' => $orders->count(),

            // ]);

            // Paid extension children with no recorded refund/void are never
            // silently deleted — they require the disposition flow.
            if (!empty($blockedExtensions)) {

                $blockedList = implode(', ', $blockedExtensions);

                if (count($blockedExtensions) === $orders->count()) {
                    return response()->json([
                        'success'              => false,
                        'requires_disposition' => true,
                        'blocked_orders'       => $blockedExtensions,
                        'message'              => "Deleting extension {$blockedList} requires an administrative disposition (verified employee, Employee ID, and reason). Delete it individually.",
                    ], 422);
                }

                return response()->json([
                    'success'              => true,
                    'requires_disposition' => true,
                    'blocked_orders'       => $blockedExtensions,
                    'message'              => "Order(s) deleted. Skipped extension(s) {$blockedList} — delete those individually with an administrative disposition (verified employee, Employee ID, and reason).",
                ], 200);
            }

            return response()->json([

                'success' => true,

                'message' => 'Order(s) deleted successfully.',

            ], 200);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Bulk Order Delete Failed', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

            ]);

            return response()->json([

                'success' => false,

                'message' => 'Failed to delete orders.',

            ], 500);
        }
    }
}