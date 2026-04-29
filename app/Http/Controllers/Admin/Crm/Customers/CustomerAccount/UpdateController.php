<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Configurations\Setting;
use App\Models\Customers\Invoice;
use App\Models\Customers\InvoiceItem;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\RedirectResponse;
use App\Helpers\CustomHelper;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\UpdateRequest;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request, $id): RedirectResponse
        {
            $validated = $request->validated();
            

            DB::beginTransaction();

            try {
              $transaction = CustomerAccount::findOrFail($id);

              $oldData = $transaction->only([
                    'amount',
                    'payment_type',
                    'payment_number_id',
                    'responsible_person_id',
                    'sales_tax',
                    'notes'
                ]);

            // Undo effect

            //  Step 1: Reverse the original effect
            CustomHelper::reverseTransactionEffect($transaction);

            // Update fields
            if (isset($validated['amount'])) {
                $transaction->amount = $validated['amount'];
            }

            if (isset($validated['payment_type'])) {
                $transaction->payment_type = $validated['payment_type'];
            }


            if (isset($validated['cheque_number'])) {
                $transaction->payment_number_id = $validated['cheque_number'];
            }

            if (isset($validated['responsible_person'])) {
                $user = User::findOrFail($validated['responsible_person']);
                $transaction->responsible_person_id = $user->id;
                $transaction->responsible_person_name = $user->full_name;
            }

            if (array_key_exists('sales_tax', $validated)) {
                $transaction->sales_tax_type = $validated['sales_tax'];
            }

            if (array_key_exists('notes', $validated)) {
                $transaction->notes = $validated['notes'];
            }

            // Always recalculate tax
            $customer = Customer::findOrFail($transaction->customer_id);
            $isTaxable = $customer->getTaxStatus() === 'Taxable';

            if ($transaction->type === 'payment' && $isTaxable) {
                $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                $transaction->sales_tax = (float) ($salesTaxSetting?->setting_value ?? 0.00);
            } else {
                $transaction->sales_tax = 0.0;
            }


            // Prepare new snapshot BEFORE save
            $newData = [
                'amount' => $validated['amount'] ?? $transaction->amount,
                'payment_type' => $validated['payment_type'] ?? $transaction->payment_type,
                'payment_number_id' => $validated['cheque_number'] ?? $transaction->payment_number_id,
                'responsible_person_id' => $transaction->responsible_person_id,
                'sales_tax' => $transaction->sales_tax,
                'notes' => $validated['notes'] ?? $transaction->notes,
            ];

            // Detect changes first
            $changes = [];

            foreach ($newData as $key => $value) {

                $old = $oldData[$key] ?? null;

                //  handle enum properly
                if ($old instanceof \BackedEnum) {
                    $old = $old->value;
                }

                if ($value instanceof \BackedEnum) {
                    $value = $value->value;
                }

                if ((string)$old !== (string)$value) {
                    $changes[$key] = [
                        'old' => $old,
                        'new' => $value
                    ];
                }
            }


            $changeNotes = [];

            foreach ($changes as $field => $vals) {
                $label = ucfirst(str_replace('_', ' ', $field));

                $changeNotes[] = "{$label}: From {$vals['old']} to {$vals['new']}";
            }

            $userName = auth()->user()?->full_name ?? 'System';
            $now = now()->format('M d, Y h:i A');
            $noteText = "{$userName} updated on {$now}: " . implode(', ', $changeNotes);

            // Append log BEFORE save
            if (!empty($changes)) {

                $logEntry = [
                    'id' => uniqid('log_'), // unique log id
                    'action' => 'transaction_updated',

                    'performed_by' => [
                       'id' => auth()->id() ?? null,
                        'name' => auth()->user()?->full_name ?? 'system'
                    ],

                    'performed_at' => now()->toDateTimeString(),

                    'changes' => $changes,
                     'note' => $noteText,
                ];

                $logs = $transaction->customer_action_log ?? [];
                $logs[] = $logEntry;

                $transaction->customer_action_log = $logs;
            }


            if (empty($changes)) {
                $now = now()->format('M d, Y h:i A');
                $logEntry = [
                    'id' => uniqid('log_'),
                    'action' => 'update_attempted_no_change',
                    'note' => "{$userName} attempted update on {$now} (no changes)",
                    'performed_by' => [
                        'id' => auth()->id() ?? null,
                        'name' => auth()->user()?->full_name ?? 'system'
                    ],
                    'performed_at' => now()->toDateTimeString(),
                ];

                $logs = $transaction->customer_action_log ?? [];
                $logs[] = $logEntry;

                $transaction->customer_action_log = $logs;
            }

            //  ONE SAVE ONLY
            $transaction->save();


            // Re-apply updated effect

                  CustomHelper::updateCreditBalance($transaction);

                DB::commit();

            // update the linked  invoice
            if ($transaction->invoice_id && $transaction->invoice_item_id) {

                $invoiceItem = InvoiceItem::findOrFail($transaction->invoice_item_id);

                // Sync InvoiceItem from Ledger
                $invoiceItem->unit = $transaction->amount;
                $invoiceItem->notes = $transaction->notes;
                $invoiceItem->responsible_person_id = $transaction->responsible_person_id;

                // Tax logic
                $salesTaxSetting = Setting::where('setting_name', 'sales_tax')->first();
                $salesTaxRate = (float) ($salesTaxSetting?->setting_value ?? 0.0);

                if (in_array($invoiceItem->type, ['charge'])) {
                    $invoiceItem->tax = $invoiceItem->unit * $salesTaxRate;
                } elseif ($invoiceItem->type === 'refund') {
                    $invoiceItem->tax = $invoiceItem->unit * $salesTaxRate;
                } else {
                    // $invoiceItem->tax = 0;
                          $invoiceItem->tax = $invoiceItem->unit * $salesTaxRate;
                }

                $invoiceItem->total = $invoiceItem->unit + $invoiceItem->tax;

                $invoiceItem->save();


                //  Recalculate Invoice totals
                 $invoice = Invoice::findOrFail($transaction->invoice_id);

                 CustomHelper::updateInvoiceSummary($invoice);
                 
            }

            CustomHelper::fixTheRunningBalance($transaction->customer_id);

                flash('Transaction successfully updated.')->success();
                // session()->flash('active_tab', 'credit');
                session(['active_tab' => 'credit']);
                return redirect()->back();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error($e);
                flash('Failed to update transaction.')->error();
                return redirect()->back()->withErrors(['error' => 'Update failed. Please try again.']);
            }
        }

}
