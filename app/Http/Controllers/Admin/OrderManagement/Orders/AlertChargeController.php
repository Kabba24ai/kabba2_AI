<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Helpers\CustomHelper;
use App\Models\Customers\CustomerAccount;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlertChargeController extends Controller
{
    public function __invoke(Request $request, string $uniqueId)
    {
        $request->validate([
            'type'               => ['required', 'in:fuel,damage'],
            'amount'             => ['required', 'numeric', 'min:0.01'],
            'notes'              => ['nullable', 'string', 'max:500'],
            'responsible_person' => ['required', 'exists:users,id'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->with('customer')->firstOrFail();
        $user  = User::findOrFail($request->responsible_person);

        DB::beginTransaction();

        try {
            $record = new CustomerAccount();
            $record->customer_id             = $order->customer_id;
            $record->order_id                = $order->id;
            $record->amount                  = $request->amount;
            $record->reason                  = $request->type === 'fuel' ? 'Fuel Charge' : 'Damages';
            $record->responsible_person_id   = $user->id;
            $record->responsible_person_name = $user->full_name;
            $record->notes                   = $request->notes;
            $record->date                    = now();
            $record->sales_tax               = 0;
            $record->sales_tax_type          = 'free';
            $record->type                    = 'charge';
            $record->fuel_alert_status       = $request->type === 'fuel'   ? 'pending' : null;
            $record->damage_alert_status     = $request->type === 'damage' ? 'pending' : null;
            $record->save();

            CustomHelper::updateCreditBalance($record);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ($request->type === 'fuel' ? 'Fuel Charge' : 'Damage Alert') . ' created successfully.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return response()->json(['success' => false, 'message' => 'Something went wrong. Please try again.'], 500);
        }
    }
}
