<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\ChargeStoreRequest;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Iam\Personnel\User ;
use App\Models\Configurations\Setting;
use App\Helpers\CustomHelper;

class ChargeStoreController extends Controller
{
    public function __invoke(ChargeStoreRequest $request)
    {
        $validated = $request->validated();

        DB::beginTransaction();

      

        try {
            $record = new CustomerAccount();
            $record->customer_id = $validated['customer_id'];
            $record->amount = $validated['amount'];
            $record->reason = $validated['reason'];
            // $record->responsible_person = $validated['responsible_person'];

            
            // Fetch user and store both ID and full_name
                $user = User::findOrFail($validated['responsible_person']);
                $record->responsible_person_id = $user->id ?? '';
                $record->responsible_person_name = $user->full_name ?? '';

            // Set sales tax type
            $record->sales_tax_type = $validated['sales_tax'] ?? null;

            
                $record->sales_tax = 0.00;
           


            $record->notes = $validated['notes'] ?? null;
            $record->date = now();
            $record->type = 'charge';

            $record->save();


              
             CustomHelper::updateCreditBalance($record);

            DB::commit();

            flash('Charge successfully added')->success();
            // session()->flash('active_tab', 'credit');
session(['active_tab' => 'credit']);

            return redirect()->back();

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            flash('Something went wrong adding charge. Please try again.')->error();
            // session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);

            Log::error($e);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred while adding the charge. Please try again.',
            ]);
        }
    }
}
