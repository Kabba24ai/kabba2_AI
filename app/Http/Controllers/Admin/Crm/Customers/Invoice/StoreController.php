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
use App\Http\Requests\Admin\Crm\Customers\Invoice\StoreRequest;
use App\Models\Configurations\Setting;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;

class StoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(StoreRequest $request)
    {
        $validated = $request->validated();

        // dd($validated);

        DB::beginTransaction();

        try {

            $invoiceItems = json_decode($validated['invoice_data'], true) ?? [];

            $invoice = Invoice::create([
                'invoice_number'    =>  $validated['invoice_number'] ?? 0,
                'invoice_date'      => CustomHelper::parseDateFromInput($validated['invoice_date']),
                'due_date'          => CustomHelper::parseDateFromInput($validated['due_date']),
                'customer_id'       => $validated['customer_id'] ,
                'invoice_created_by' => auth()->id(),
                'subtotal'          => $validated['subtotal'] ?? 0,
                'sales_tax'         => $validated['tax'] ?? 0,
                'total'             => $validated['total'] ?? 0,
                'invoice_notes'     => $validated['invoice_notes'] ?? null,

                      'paid_amount'             =>  0,
                      'open_amount'             => $validated['total'] ?? 0,
            ]);

            // Save invoice items
            foreach ($invoiceItems as $item) {
                $invoiceItem =  InvoiceItem::create([
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

                // ---------------------------------------
                // Create CustomerAccount Ledger Entry
                // ---------------------------------------

                $record = new CustomerAccount();
                $record->customer_id = $invoice->customer_id;
                $record->reason = $invoiceItem->item_name;

                $type = $invoiceItem->type;

                $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);
            
                $salesTax = 0;
                $salesTaxType = null;

                if (in_array($type, ['charge', 'order'])) {

                
                    $salesTax = $salesTaxRate;
                    $salesTaxType = 'add';

                } elseif ($type === 'discount') {

                
                    $salesTax = 0; // discount has no tax
                    $salesTaxType = null;

                } elseif ($type === 'refund') {

                
                    $salesTax = $salesTaxRate; // refund reduces tax
                    $salesTaxType = null;
                }

                $record->amount = $invoiceItem->unit ?? 0;
                $record->sales_tax = $salesTax;
                $record->sales_tax_type = $salesTaxType;

                // Responsible Person
                if (!empty($item['responsible_id'])) {
                    $user = User::find($item['responsible_id']);
                    if ($user) {
                        $record->responsible_person_id = $user->id;
                        $record->responsible_person_name = $user->full_name ?? '';
                    }
                }

                $record->notes = $invoiceItem->notes ?? null;
                $record->date = now();
                $record->type = $type;

                $record->invoice_id = $invoice->id;
                $record->invoice_item_id = $invoiceItem->id;

                $record->save();

                CustomHelper::updateCreditBalance($record);

            }

            $customer = Customer::find($validated['customer_id']);

            // Loop through all invoice items of type 'order'
            $invoice->items()->where('type', 'order')->get()->each(function ($invoiceItem) {
                $orderProduct = $invoiceItem->orderProduct;

                if ($orderProduct && $orderProduct->order) {
                    $order = $orderProduct->order;

                    // Update the invoice_id on the order
                    $order->invoice_id = $invoiceItem->invoice_id;
                    $order->save();
                }
            });

            DB::commit();

            flash('Invoice created successfully.')->success();

            // session()->flash('active_tab', 'invoices');
            
            session(['active_tab' => 'invoices']);


            return redirect()->route('admin.crm.customers.view', $customer->unique_id);

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
