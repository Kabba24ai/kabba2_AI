<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Customers\Customer;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\Log;

// Request
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\PaymentStoreRequest;
use Illuminate\Support\Carbon;
use App\Helpers\CustomHelper;
use App\Models\Iam\Personnel\User ;

class PaymentStoreController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(PaymentStoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            $record->order_id = $validated['order_id'] ?? null;
            $record->balance = $validated['balance'] ?? 0;
            $record->amount = $validated['amount'];
            $record->payment_type = $validated['payment_type'];


              // Fetch user and store both ID and full_name
                $user = User::findOrFail($validated['responsible_person']);
                $record->responsible_person_id = $user->id ?? '';
                $record->responsible_person_name = $user->full_name ?? '';

            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->payment_number_id = $validated['payment_number_id'] ?? null;
            $record->reason = $validated['reason'] ?? null;
            $record->sales_tax = $validated['sales_tax'] ?? 0;
            $record->type = 'payment';

            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            flash('Payment recorded successfully.')->success();
            session()->flash('active_tab', 'credit');

            
            return redirect()->back();
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong while recording the payment.')->error();
            session()->flash('active_tab', 'credit');
            Log::info($e);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while recording the payment.',
            ]);
        }
    }

}
