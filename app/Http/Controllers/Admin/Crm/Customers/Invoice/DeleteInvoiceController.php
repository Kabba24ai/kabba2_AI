<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;
use App\Models\Customers\CustomerAccount;

use App\Models\Orders\Order;

use App\Helpers\CustomHelper;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteInvoiceController extends Controller
{
    public function __invoke($unique_id): RedirectResponse
    {
        DB::beginTransaction();

        try {

            $invoice = Invoice::with('items')
                ->where('unique_id', $unique_id)
                ->firstOrFail();

            $customer_id = $invoice->customer_id;


            /*
            |--------------------------------------------------------------------------
            | Handle CustomerAccount depending on invoice type
            |--------------------------------------------------------------------------
            */

            $accounts = CustomerAccount::where('invoice_id', $invoice->id)->get();

            if ($invoice->invoice_type === 'account') {

            foreach ($accounts as $account) {

                // Unlink transaction from invoice
                $account->invoice_id = null;
                $account->invoice_item_id = null;
                $account->save();

                /*
                |--------------------------------------------------------------------------
                | If transaction belongs to an order → unlink the order from invoice
                |--------------------------------------------------------------------------
                */

                if ($account->order_id) {

                    $order = Order::where('id', $account->order_id)
                        ->where('invoice_id', $invoice->id)
                        ->first();

                    if ($order) {
                        $order->update(['invoice_id' => null]);
                    }
                }

                if ($account->type === 'account_invoice') {

                  $account->delete();

                }

            }
        } else {

                foreach ($accounts as $account) {
                    CustomHelper::reverseTransactionEffect($account);
                    $account->delete();
                }
            }

           

            //  Delete invoice items
            
           $invoice->items()->delete();


            /*
            |--------------------------------------------------------------------------
            | Delete invoice
            |--------------------------------------------------------------------------
            */
            $invoice->delete();

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Fix running balance
            |--------------------------------------------------------------------------
            */
            CustomHelper::fixTheRunningBalance($customer_id);

            flash('Invoice successfully deleted.')->success();

            return redirect()->back();

        } catch (\Throwable $e) {

            DB::rollBack();

            Log::error($e);

            flash('Failed to delete invoice.')->error();

            return redirect()->back();
        }
    }
}