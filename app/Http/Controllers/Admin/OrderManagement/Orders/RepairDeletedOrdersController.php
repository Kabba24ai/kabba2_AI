<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Models
use App\Models\Orders\Order;
use App\Models\Customers\CustomerAccount;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;

// Helpers
use App\Helpers\CustomHelper;

class RepairDeletedOrdersController extends Controller
{
    public function __invoke()
    {
        DB::beginTransaction();

        try {

            // Log::info('Repair Deleted Orders Started');

            $orders = Order::onlyTrashed()->get();

            $processed = 0;

            foreach ($orders as $order) {

                // Log::info('Processing Deleted Order', [

                //     'order_id' => $order->id,

                //     'unique_id' => $order->unique_id,

                // ]);

                $transactions = CustomerAccount::withTrashed()
                    ->where('order_id', $order->id)
                    ->get();

                foreach ($transactions as $transaction) {

                    /*
                    |--------------------------------------------------------------------------
                    | Invoice Cleanup
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $transaction->invoice_id &&
                        $transaction->invoice_item_id
                    ) {

                        $invoice = Invoice::find(
                            $transaction->invoice_id
                        );

                        $invoiceItem = InvoiceItem::find(
                            $transaction->invoice_item_id
                        );

                        if ($invoiceItem) {

                            $invoiceItem->delete();

                            // Log::info('Invoice Item Deleted', [

                            //     'invoice_item_id' => $invoiceItem->id,

                            // ]);
                        }

                        if ($invoice) {

                            CustomHelper::updateInvoiceSummary(
                                $invoice
                            );

                            // Log::info('Invoice Updated', [

                            //     'invoice_id' => $invoice->id,

                            // ]);
                        }
                    }

                   /*
                    |--------------------------------------------------------------------------
                    | Reverse Transaction Effect
                    |--------------------------------------------------------------------------
                    */

                    $customerId = $transaction->customer_id;

                    if (!$transaction->trashed()) {

                        CustomHelper::reverseTransactionEffect(
                            $transaction
                        );

                        $transaction->delete();

                        // Log::info('Transaction Deleted', [

                        //     'transaction_id' => $transaction->id,

                        // ]);

                    } else {

                        // Log::info('Transaction Already Deleted', [

                        //     'transaction_id' => $transaction->id,

                        // ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Fix Running Balance
                    |--------------------------------------------------------------------------
                    */

                    CustomHelper::fixTheRunningBalance(
                        $customerId
                    );

                    // Log::info('Running Balance Fixed', [

                    //     'customer_id' => $customerId,

                    // ]);
                }

                $processed++;
            }

            DB::commit();

            return response()->json([

                'success' => true,

                'message' => 'Repair completed successfully.',

                'processed_orders' => $processed,

            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error('Repair Failed', [

                'message' => $e->getMessage(),

                'trace' => $e->getTraceAsString(),

            ]);

            return response()->json([

                'success' => false,

                'message' => $e->getMessage(),

            ], 500);
        }
    }
}