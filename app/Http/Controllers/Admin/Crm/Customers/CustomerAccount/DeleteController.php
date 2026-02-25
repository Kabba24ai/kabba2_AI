<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Helpers\CustomHelper;

use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DeleteController extends Controller
{
   public function __invoke($id): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $transaction = CustomerAccount::findOrFail($id);

            $customer_id = $transaction->customer_id ;

            //  If linked to invoice
        if ($transaction->invoice_id && $transaction->invoice_item_id) {

            $invoice = Invoice::findOrFail($transaction->invoice_id);
            $invoiceItem = InvoiceItem::find($transaction->invoice_item_id);

            if ($invoiceItem) {
                $invoiceItem->delete();
            }

            // Recalculate invoice totals
            CustomHelper::updateInvoiceSummary($invoice);
        }

            // Reverse transaction effect
            CustomHelper::reverseTransactionEffect($transaction);

            $transaction->delete();

            DB::commit();

            
            CustomHelper::fixTheRunningBalance($customer_id);
            

            flash('Transaction successfully deleted.')->success();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);
            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            session(['active_tab' => 'credit']);
            Log::error($e);
            flash('Failed to delete transaction.')->error();
            return redirect()->back()->withErrors(['error' => 'Delete failed. Please try again.']);
        }
    }

}
