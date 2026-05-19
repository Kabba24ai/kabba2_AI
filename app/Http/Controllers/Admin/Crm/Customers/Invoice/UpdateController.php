<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;

use App\Models\Customers\CustomerAccount;
use App\Models\Configurations\Setting;
use App\Models\Iam\Personnel\User;

use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;
use App\Events\Admin\Invoices\InvoicePaidEvent;

use App\Http\Requests\Admin\Crm\Customers\Invoice\UpdateRequest;

class UpdateController extends Controller
{
    /**
     * Handle updating an existing invoice.
     */
    public function __invoke(UpdateRequest $request, string $unique_id)
    {

        // dd($request->all());
        // die();

        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Find the invoice by unique_id
            $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

                    $paymentAmount = (float) $validated['paid_amount'];

                    // Recalculate open amount
                    $open_amount = max(0, $validated['total'] - $paymentAmount);

            $updateData = [
                'invoice_number' => $validated['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date'   => CustomHelper::parseDateFromInput($validated['invoice_date']),
                'due_date'       => CustomHelper::parseDateFromInput($validated['due_date']),
                'customer_id'    => $validated['customer_id'],
                'subtotal'       => $validated['subtotal'] ?? 0,
                'sales_tax'      => $validated['tax'] ?? 0,
                'total'          => $validated['total'] ?? 0,
                'paid_amount'          => $paymentAmount ,
                'open_amount'          => $open_amount ,
                'invoice_notes'  => $validated['invoice_notes'] ?? null,
                'payment_method' => $validated['payment_method'] ?? $invoice->payment_method,
            ];

            // Only update invoice_status if provided
            if (isset($validated['invoice_status'])) {
                $updateData['invoice_status'] = $validated['invoice_status'];
            }

            $originalInvoiceData = $invoice->getOriginal();

            // Perform the update
            $invoice->update($updateData);

            $changedFields = $invoice->getChanges();


            $invoiceItems = json_decode($validated['invoice_data'], true) ?? [];

            // Collect all current DB item IDs for this invoice
            $existingItemIds = InvoiceItem::where('invoice_id', $invoice->id)->pluck('item_id')->toArray();

            // Track IDs that we keep (existing or newly created)
            $keepItemIds = [];

                $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);
            

            foreach ($invoiceItems as $item) {

            $existingItem = InvoiceItem::where('invoice_id', $invoice->id)
                ->where('item_id', $item['id'])
                ->first();

                if ($existingItem) {

                    // Update existing item by item_id
                     $existingItem->update([
                            'type'                  => $item['type'] ?? null,
                            'item_name'             => $item['name'] ?? null,
                            'qty'                   => $item['qty'] ?? 1,
                            'sku'                   => $item['sku'] ?? null,
                            'unit'                  => $item['unit'] ?? 0,
                            'tax'                   => $item['tax'] ?? 0,
                            'sales_tax_type' => $item['sales_tax'] ?? 'add',
                            'total'                 => $item['total'] ?? 0,
                            'extras'                => $item['extras'] ?? null,
                            'notes'                 => $item['notes'] ?? null,
                            'reference'             => $item['reference'] ?? null,
                            'responsible_person_id' => $item['responsible_id'] ?? null,
                        ]
                    );

                    // ======================================
                    // Update CustomerAccount for edited item
                    // ======================================

                    $account = CustomerAccount::where('invoice_id', $invoice->id)
                        ->where('invoice_item_id', $existingItem->id)
                        ->first();

                    if ($account) {

                        //  Reverse old effect
                        CustomHelper::reverseTransactionEffect($account);

                        //  Update fields
                        $account->reason = $existingItem->item_name;
                        $account->amount = $existingItem->unit ?? 0;
                        $account->type   = $existingItem->type;
                        $account->notes  = $existingItem->notes ?? null;

                        // if (in_array($existingItem->type, ['charge', 'order'])) {
                        //     $account->sales_tax = $salesTaxRate;
                        //     $account->sales_tax_type = 'add';
                        // } elseif ($existingItem->type === 'discount') {
                        //   $account->sales_tax = $salesTaxRate;
                        //     $account->sales_tax_type = null;
                        // } elseif ($existingItem->type === 'refund') {
                        //     $account->sales_tax = $salesTaxRate;
                        //     $account->sales_tax_type = null;
                        // }


                        $account->sales_tax = $existingItem->tax ?? 0;

                        $account->sales_tax_type = $existingItem->sales_tax_type ?? 'add';

                        if ($existingItem->sales_tax_type === 'reverse') {
                            $account->amount =  $existingItem->total ?? 0;
                        } else {
                            $account->amount =  $existingItem->unit ?? 0;
                        }

                        // Responsible person
                        if (!empty($item['responsible_id'])) {
                            $user = User::find($item['responsible_id']);
                            if ($user) {
                                $account->responsible_person_id = $user->id;
                                $account->responsible_person_name = $user->full_name ?? '';
                            }
                        }

                        $account->save();

                        // Re-apply updated effect
                        CustomHelper::updateCreditBalance($account);
                    }

                    
                    $keepItemIds[] = $item['id'];

                } else {


                    // Create new item
                    $newItem =  InvoiceItem::create([
                        'invoice_id'            => $invoice->id,
                        'type'                  => $item['type'] ?? null,
                        'item_name'             => $item['name'] ?? null,
                        'item_id'               => $item['id'] ?? null,
                        'qty'                   => $item['qty'] ?? 1,
                        'sku'                   => $item['sku'] ?? null,
                        'unit'                  => $item['unit'] ?? 0,
                        'tax'                   => $item['tax'] ?? 0,
                        'sales_tax_type' => $item['sales_tax'] ?? 'add',
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
                $record->reason = $newItem->item_name;

                $type = $newItem->type;

            
                // $salesTax = 0;
                // $salesTaxType = null;

                // if (in_array($type, ['charge', 'order'])) {
                //     $salesTax = $salesTaxRate;
                //     $salesTaxType = 'add';

                // } elseif ($type === 'discount') {
                    
                //     $salesTax = $salesTaxRate; // refund reduces tax

                //     $salesTaxType = null;

                // } elseif ($type === 'refund') {

                //     $salesTax = $salesTaxRate; // refund reduces tax
                //     $salesTaxType = null;
                // }

                // $record->amount = $newItem->unit ?? 0;
                // $record->sales_tax = $salesTax;
                // $record->sales_tax_type = $salesTaxType;


                $record->sales_tax = $newItem->tax ?? 0;

                $record->sales_tax_type = $newItem->sales_tax_type ?? 'add';

                if ($newItem->sales_tax_type === 'reverse') {
                    $record->amount = $newItem->total ?? 0;
                } else {
                    $record->amount = $newItem->unit ?? 0;
                }

                // Responsible Person
                if (!empty($item['responsible_id'])) {
                    $user = User::find($item['responsible_id']);
                    if ($user) {
                        $record->responsible_person_id = $user->id;
                        $record->responsible_person_name = $user->full_name ?? '';
                    }
                }

                $record->notes = $newItem->notes ?? null;
                $record->date = now();
                $record->type = $type;

                $record->invoice_id = $invoice->id;
                $record->invoice_item_id = $newItem->id;

                $record->save();

                CustomHelper::updateCreditBalance($record);
                    $keepItemIds[] = $newItem->item_id; // mark as kept
                }
            }

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


            if (!empty($keepItemIds)) {
                $deletedItems = InvoiceItem::where('invoice_id', $invoice->id)
                    ->whereNotIn('item_id', $keepItemIds)
                    ->get();
            } else {
                $deletedItems = InvoiceItem::where('invoice_id', $invoice->id)->get();
            }

            // Unlink orders for deleted invoice items of type 'order'
            foreach ($deletedItems as $deletedItem) {
                if ($deletedItem->type === 'order' && $deletedItem->orderProduct && $deletedItem->orderProduct->order) {
                    $order = $deletedItem->orderProduct->order;
                    $order->invoice_id = null;
                    $order->save();
                }
                
                //  else {

                //   $customerAccount = CustomerAccount::where('invoice_id', $invoice->id)
                //     ->where('invoice_item_id', $deletedItem->id)
                //     ->get(); // get all matching

                //     foreach ($customerAccount as $account) {
                //         $account->invoice_id = null;
                //         $account->invoice_item_id = null; 
                //         $account->save();
                //     }

                // }
            }

            foreach ($deletedItems as $deletedItem) {

                $account = CustomerAccount::where('invoice_id', $invoice->id)
                    ->where('invoice_item_id', $deletedItem->id)
                    ->first();

                if ($account) {
                    CustomHelper::reverseTransactionEffect($account);
                    $account->delete();
                }
            }

            // Actually delete the invoice items
            $deletedCount = $deletedItems->each->delete();

            $customer = Customer::find($validated['customer_id']);


            // Loop through all invoice items of type 'order'
            $invoice->items()->where('type', 'order')->get()->each(function ($invoiceItem) use ($invoice, $validated, $customer) {
                $orderProduct = $invoiceItem->orderProduct;

                if ($orderProduct && $orderProduct->order) {
                    $order = $orderProduct->order;

                    // Update the invoice_id on the order
                    $order->invoice_id = $invoiceItem->invoice_id;
                    $order->save();

                    // Record payment against the order

                    // Determine payment method and status dynamically
                    // $paymentMethod = $validated['payment_method'] ?? 'Card'; // default to Card
                    // $paymentStatus = match ($paymentMethod) {
                    //     'card'   => 'Invoice Card',
                    //     'online' => 'Invoice Online',
                    //     'cash'   => 'Invoice Cash',
                    //     'cheque'   => 'Invoice Cheque',
                    //     'other'   => 'Invoice Other',
                    //     default  => 'Invoice Card',
                    // };

                    // $payment = $order->payments()->create([
                    //     'payment_datetime'     => now(),
                    //     'payment_method'       => ucfirst($paymentMethod),
                    //     'status'               =>  $paymentStatus,
                    //     'amount'               => $invoice->total,
                    //     'transaction_id'       => null,
                    //     'auth_code'            =>  null,
                    //     'customer_profile_id'  =>  null,
                    //     'payment_profile_id'   =>  null,
                    //     'card_number'          =>  null,
                    //     'card_first_name'      => $validated['firstName'] ?? null, // <-- fixed
                    //     'card_last_name'       => $validated['lastName'] ?? null,   // <-- fixed
                    //     'created_by_id'        => $customer->id,
                    //     'created_by_type'      => Customer::class,
                    // ]);


                    // $orderActionType = 'invoice_payment_from_admin';
                    // $employee = auth()->user();

                    // Fire OrderPlaced event
                    //event(new OrderPlacedEvent($order, $customer, $payment, $orderActionType, $employee));
  

                    // event(new InvoicePaidEvent($order, $customer, $payment, $employee));
 

                    // Create receipt

                    // if (
                    //     $order->invoice &&
                    //     $order->invoice->invoice_status === 'paid' &&
                    //     $order->receipt_status === 'pending'
                    // ) {

                    //     $receipt = Receipt::create([
                    //         'invoice_id'         => $order->invoice->id,
                    //         'customer_id'        => $order->customer->id,
                    //         'order_id'=> $order->id ,
                    //         'receipt_created_by' => auth()->id(),
                    //         'payment_method'     => $order->invoice->payment_method,
                    //         'receipt_date'       => now(),
                    //         'order_date'         => $order->order_date,
                    //         'payment_status'     => 'paid',
                    //         'subtotal'           => $order->invoice->subtotal,
                    //         'sales_tax'          => $order->invoice->sales_tax,
                    //         'total'              => $order->invoice->total,
                    //     ]);

                    //     // Create receipt items based on invoice items
                    //     foreach ($order->invoice->items as $invItem) {
                    //         $receipt->items()->create([
                    //             'type'      => $invItem->type,
                    //             'item_name' => $invItem->item_name,
                    //             'unit'      => $invItem->unit,
                    //             'qty'       => $invItem->qty,
                    //             'tax'       => $invItem->tax,
                    //             'total'     => $invItem->total,
                    //             'item_id'=> $invItem->item_id,
                    //         ]);
                    //     }

                    //     // Update order to mark receipt as created
                    //     $order->receipt_status = 'created';
                    //     $order->save();

                    //     Log::info('Receipt Created for Order', [
                    //         'order_id'   => $order->id,
                    //         'order_num'  => $order->order_number,
                    //         'receipt_id' => $receipt->id,
                    //     ]);
                    // }
                }
            });

         
            DB::commit();

            CustomHelper::fixTheRunningBalance($validated['customer_id']);
            

            flash('Invoice updated successfully.')->success();

            // session()->flash('active_tab', 'invoices');
            session(['active_tab' => 'invoices']);

            return redirect()->route('admin.crm.customers.view', $customer->unique_id);

            // return redirect()->route('admin.crm.customers.invoice.index', $invoice->customer->unique_id);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Invoice update failed', [
                'message'   => $e->getMessage(),
                'request'   => $request->all(),
            ]);

            flash('Something went wrong while updating the invoice.')->error();

            return redirect()->back()->withInput()->withErrors(['error' => 'An error occurred while updating the invoice.']);
        }
    }
}
