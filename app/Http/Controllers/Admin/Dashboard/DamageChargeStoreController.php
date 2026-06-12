<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DamageChargeStoreController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'customer_id'        => ['required', 'exists:customers,id'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
            'sales_tax_type'     => ['nullable', 'in:add,free,reverse'],
        ]);

        $user = User::findOrFail($request->responsible_person);

        DB::beginTransaction();

        try {
            $record                          = new CustomerAccount();
            $record->customer_id             = $request->customer_id;
            $record->amount                  = $request->amount;
            $record->reason                  = 'Damages';
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $request->notes;
            $record->date                    = now();
            $record->sales_tax_type          = $request->sales_tax_type ?? 'free';
            $record->sales_tax               = 0;
            $record->type                    = 'charge';
            $record->damage_alert_status     = 'pending';
            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Damage Alert created successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
