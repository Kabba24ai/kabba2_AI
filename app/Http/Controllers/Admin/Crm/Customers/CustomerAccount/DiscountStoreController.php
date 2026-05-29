<?php

namespace App\Http\Controllers\Admin\Crm\Customers\CustomerAccount;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Crm\Customers\CustomerAccount\DiscountStoreRequest;
use App\Models\Customers\CustomerAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Iam\Personnel\User ;
use App\Helpers\CustomHelper;

class DiscountStoreController extends Controller
{
    public function __invoke(DiscountStoreRequest $request)
    {

        // dd($request->all());

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
            $record->type = 'discount';

            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            flash('Discount successfully applied')->success();

            session(['active_tab' => 'credit']);

            return redirect()->back();

        } catch (\Throwable $e) {

            DB::rollBack();
            report($e);

            flash('Something went wrong apply discount..  Please try again. ')->error();
            //  session()->flash('active_tab', 'credit');
            session(['active_tab' => 'credit']);
            Log::info($e);

            return redirect()->back()->withInput()->withErrors([
                'error' => 'An error occurred apply discount .  Please try again.',
            ]);

           
        }
    }
}
