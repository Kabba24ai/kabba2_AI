<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Configurations\Setting;

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

            $transaction->save();

            // Re-apply updated effect

                  CustomHelper::updateCreditBalance($transaction);

                DB::commit();

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
