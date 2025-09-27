<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Invoice;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;
use Illuminate\Support\Facades\Log;

use App\Http\Requests\Admin\Crm\Customers\Invoice\StoreRequest;

class UpdateController extends Controller
{
    /**
     * Handle updating an existing invoice.
     */
    public function __invoke(StoreRequest $request, string $unique_id)
    {
        $validated = $request->validated();


        Log::info('Invoice update request received', [
            'unique_id' => $unique_id,
            'validated_data' => $validated,
            'user_id' => auth()->id(),
        ]);

        DB::beginTransaction();

        try {
            // Find the invoice by unique_id
            $invoice = Invoice::where('unique_id', $unique_id)->firstOrFail();

            // Update main invoice fields
            $invoice->update([
                'invoice_number'    => $validated['invoice_number'] ?? $invoice->invoice_number,
                'invoice_date'      => CustomHelper::parseDateFromInput($validated['invoice_date']),
                'due_date'          => CustomHelper::parseDateFromInput($validated['due_date']),
                'customer_id'       => $validated['customer_id'],
                'subtotal'          => $validated['subtotal'] ?? 0,
                'sales_tax'         => $validated['tax'] ?? 0,
                'total'             => $validated['total'] ?? 0,
                'invoice_notes'     => $validated['invoice_notes'] ?? null,
            ]);

            Log::info('Invoice main data updated', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number
            ]);

            $invoiceItems = json_decode($validated['invoice_data'], true) ?? [];
            Log::info('Decoded invoice items', ['invoice_items' => $invoiceItems]);

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

                    Log::info('Invoice item updated', [
                        'db_id' => $updatedItem->id,
                        'item_id' => $item['id'],
                        'invoice_id' => $invoice->id
                    ]);

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
                    Log::info('Invoice item created', [
                        'db_id' => $newItem->id,
                        'item_id' => $item['id'] ?? null,
                        'invoice_id' => $invoice->id
                    ]);

                    $keepItemIds[] = $newItem->item_id; // mark as kept

                }
            }

            // Delete DB items that were removed in the front-end
            // Delete DB items that were removed in the front-end
            $deletedCount = InvoiceItem::where('invoice_id', $invoice->id)
                ->whereNotIn('item_id', $keepItemIds)
                ->delete();

            Log::info('Deleted removed invoice items', [
                'deleted_count' => $deletedCount,
                'keep_item_ids' => $keepItemIds,
                'invoice_id' => $invoice->id
            ]);

            DB::commit();

            flash('Invoice updated successfully.')->success();

            return redirect()->route('admin.crm.customers.invoice.index', $invoice->customer->unique_id);
        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('Invoice update failed', [
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
                'request'   => $request->all(),
                'user_id'   => auth()->id(),
            ]);

            flash('Something went wrong while updating the invoice.')->error();

            return redirect()->back()->withInput()->withErrors(['error' => 'An error occurred while updating the invoice.']);
        }
    }
}
