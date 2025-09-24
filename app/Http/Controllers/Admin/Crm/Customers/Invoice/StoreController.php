<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Customers\Invoice;
use App\Models\Customers\Customer;
use App\Models\Customers\InvoiceItem;
use Illuminate\Support\Carbon;
use App\Helpers\CustomHelper;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        // dd($request->all());
        // die();

        DB::beginTransaction();

        try {
          
            $invoiceItems = json_decode($request->input('invoice_data'), true) ?? [];

            $invoice = Invoice::create([
                'invoice_number'    => $request->input('invoice_number') ?? 0,
                'invoice_date'      => CustomHelper::parseDateFromInput($request->input('invoice_date')),
                'due_date'          => CustomHelper::parseDateFromInput($request->input('due_date')),
                'customer_id'       => $request->input('customer_id'),
                'invoice_created_by' => auth()->id(),
                'subtotal'          => $request->input('subtotal') ?? 0,
                'sales_tax'         => $request->input('tax') ?? 0,
                'total'             => $request->input('total') ?? 0,
                'invoice_notes'     => $request->input('invoice_notes') ?? null,
            ]);

            // Save invoice items
            foreach ($invoiceItems as $item) {
                InvoiceItem::create([
                    'invoice_id'            => $invoice->id,
                    'type'                  => $item['type'] ?? null,
                    'item_name'             => $item['name'] ?? null,
                    'item_id'               => $item['id'] ?? null,
                    'qty'                   => $item['qty'] ?? 1,
                    'sku'                   => $item['sku'] ?? null,
                    'unit'                  => $item['unit'] ?? 0,
                    'tax'                   => $item['tax'] ?? 0,
                    'total'                 => $item['total'] ?? 0,
                    'extras'                => $item['extras'] ?? null,
                    'notes'                 => $item['notes'] ?? null,
                    'reference'             => $item['reference'] ?? null,
                    'responsible_person_id' => $item['responsible_id'] ?? null,
                ]);
            }

            $customer = Customer::find($request->input('customer_id'));

            DB::commit();

            flash('Invoice created successfully.')->success();

            session()->flash('active_tab', 'invoices');

            return redirect()->route('admin.crm.customers.invoice.index', $customer->unique_id) ;

        } catch (\Throwable $e) {
            DB::rollBack();

            // Log full error details for debugging
            \Log::error('Invoice creation failed', [
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
                'request'   => request()->all(), // log the request data (be careful with sensitive info)
                'user_id'   => auth()->id(),
            ]);

            flash('Something went wrong while creating the invoice.')->error();

            return redirect()
                ->back()
                ->withInput()
                ->withErrors(['error' => 'An error occurred while creating the invoice.']);
        }
    }
}
