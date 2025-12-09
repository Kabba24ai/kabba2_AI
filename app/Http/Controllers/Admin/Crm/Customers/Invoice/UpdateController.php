<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;
use App\Models\Customers\Customer;
use App\Models\Customers\Receipt;



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
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            // Find the invoice by unique_id
            $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

            $updateData = [
                'invoice_number' => $validated['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date'   => CustomHelper::parseDateFromInput($validated['invoice_date']),
                'due_date'       => CustomHelper::parseDateFromInput($validated['due_date']),
                'customer_id'    => $validated['customer_id'],
                'subtotal'       => $validated['subtotal'] ?? 0,
                'sales_tax'      => $validated['tax'] ?? 0,
                'total'          => $validated['total'] ?? 0,
                'invoice_notes'  => $validated['invoice_notes'] ?? null,
                'payment_method' => $validated['payment_method'] ?? $invoice->payment_method,
            ];

            // Only update invoice_status if provided
            if (isset($validated['invoice_status'])) {
                $updateData['invoice_status'] = $validated['invoice_status'];
            }

            // Perform the update
            $invoice->update($updateData);

            $invoiceItems = json_decode($validated['invoice_data'], true) ?? [];
            // Log::info('Decoded invoice items', ['invoice_items' => $invoiceItems]);

            // Collect all current DB item IDs for this invoice
            $existingItemIds = InvoiceItem::where('invoice_id', $invoice->id)->pluck('item_id')->toArray();

            // Track IDs that we keep (existing or newly created)
            $keepItemIds = [];

            foreach ($invoiceItems as $item) {
                if ($item['id']) {
                    // Update existing item by item_id
                    $updatedItem =  InvoiceItem::updateOrCreate(
                        ['item_id' => $item['id'], 'invoice_id' => $invoice->id],
                        [
                            'type'                  => $item['type'] ?? null,
                            'item_name'             => $item['name'] ?? null,
                            'qty'                   => $item['qty'] ?? 1,
                            'sku'                   => $item['sku'] ?? null,
                            'unit'                  => $item['unit'] ?? 0,
                            'tax'                   => $item['tax'] ?? 0,
                            'total'                 => $item['total'] ?? 0,
                            'extras'                => $item['extras'] ?? null,
                            'notes'                 => $item['notes'] ?? null,
                            'reference'             => $item['reference'] ?? null,
                            'responsible_person_id' => $item['responsible_id'] ?? null,
                        ]
                    );


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
                        'total'                 => $item['total'] ?? 0,
                        'extras'                => $item['extras'] ?? null,
                        'notes'                 => $item['notes'] ?? null,
                        'reference'             => $item['reference'] ?? null,
                        'responsible_person_id' => $item['responsible_id'] ?? null,
                    ]);


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


            // Get the items that are being deleted
            $deletedItems = InvoiceItem::where('invoice_id', $invoice->id)
                ->whereNotIn('item_id', $keepItemIds)
                ->get();

            // Unlink orders for deleted invoice items of type 'order'
            foreach ($deletedItems as $deletedItem) {
                if ($deletedItem->type === 'order' && $deletedItem->orderProduct && $deletedItem->orderProduct->order) {
                    $order = $deletedItem->orderProduct->order;
                    $order->invoice_id = null;
                    $order->save();
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
                    $paymentMethod = $validated['payment_method'] ?? 'Card'; // default to Card
                    $paymentStatus = match ($paymentMethod) {
                        'card'   => 'Invoice Card',
                        'online' => 'Invoice Online',
                        'cash'   => 'Invoice Cash',
                        'cheque'   => 'Invoice Cheque',
                        'other'   => 'Invoice Other',
                        default  => 'Invoice Card',
                    };

                    $payment = $order->payments()->create([
                        'payment_datetime'     => now(),
                        'payment_method'       => ucfirst($paymentMethod),
                        'status'               =>  $paymentStatus,
                        'amount'               => $invoice->total,
                        'transaction_id'       => null,
                        'auth_code'            =>  null,
                        'customer_profile_id'  =>  null,
                        'payment_profile_id'   =>  null,
                        'card_number'          =>  null,
                        'card_first_name'      => $validated['firstName'] ?? null, // <-- fixed
                        'card_last_name'       => $validated['lastName'] ?? null,   // <-- fixed
                        'created_by_id'        => $customer->id,
                        'created_by_type'      => Customer::class,
                    ]);

                    Log::info('Recorded Payment for Order', [
                        'order_id' => $order->id,
                        'order_number' => $order->order_number,
                        'payment_id' => $payment->id,
                        'amount' => $payment->amount,
                        'status' => $payment->status,
                    ]);

                    // $orderActionType = 'invoice_payment_from_admin';
                    $employee = auth()->user();

                    // Fire OrderPlaced event
                    //event(new OrderPlacedEvent($order, $customer, $payment, $orderActionType, $employee));
  

                    event(new InvoicePaidEvent($order, $customer, $payment, $employee));
 

                    // Create receipt

                    if (
                        $order->invoice &&
                        $order->invoice->invoice_status === 'paid' &&
                        $order->receipt_status === 'pending'
                    ) {

                        $receipt = Receipt::create([
                            'invoice_id'         => $order->invoice->id,
                            'customer_id'        => $order->customer->id,
                            'order_id'=> $order->id ,
                            'receipt_created_by' => auth()->id(),
                            'payment_method'     => $order->invoice->payment_method,
                            'receipt_date'       => now(),
                            'order_date'         => $order->order_date,
                            'payment_status'     => 'paid',
                            'subtotal'           => $order->invoice->subtotal,
                            'sales_tax'          => $order->invoice->sales_tax,
                            'total'              => $order->invoice->total,
                        ]);

                        // Create receipt items based on invoice items
                        foreach ($order->invoice->items as $invItem) {
                            $receipt->items()->create([
                                'type'      => $invItem->type,
                                'item_name' => $invItem->item_name,
                                'unit'      => $invItem->unit,
                                'qty'       => $invItem->qty,
                                'tax'       => $invItem->tax,
                                'total'     => $invItem->total,
                                'item_id'=> $invItem->item_id,
                            ]);
                        }

                        // Update order to mark receipt as created
                        $order->receipt_status = 'created';
                        $order->save();

                        Log::info('Receipt Created for Order', [
                            'order_id'   => $order->id,
                            'order_num'  => $order->order_number,
                            'receipt_id' => $receipt->id,
                        ]);
                    }
                }
            });

            Log::info('Linked Orders to Invoice', [
                'invoice_id' => $invoice->id,
                'orders' => $invoice->items()->where('type', 'order')->get()->map(function ($item) {
                    return $item->orderProduct?->order?->order_number;
                })
            ]);


            DB::commit();

            flash('Invoice updated successfully.')->success();

            session()->flash('active_tab', 'invoices');

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
