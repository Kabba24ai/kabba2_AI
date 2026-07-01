<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderProduct;
use App\Services\BillingEngine;
use Illuminate\Http\Request;

class UncollectibleController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $request->validate([
            'resolved_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        $charge = BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail();

        if (! $charge->status?->isOpen()) {
            return response()->json(['success' => false, 'message' => 'This charge is already closed.'], 422);
        }

        BillingEngine::markUncollectible($charge, (int) $request->resolved_by);

        if ($charge->legacyCustomerAccount) {
            $ca = $charge->legacyCustomerAccount;
            if ($ca->fuel_alert_status === 'pending') {
                $ca->fuel_alert_status = 'uncollectible';
                $ca->save();
            } elseif ($ca->damage_alert_status === 'pending') {
                $ca->damage_alert_status = 'uncollectible';
                $ca->save();
            }
        }

        // Mobile-originated charges have no CA record — sync OP status directly.
        if ($charge->order_product_id && ! $charge->customer_account_id) {
            $op = OrderProduct::find($charge->order_product_id);
            if ($op) {
                $statusField = $charge->billing_charge_type?->value === 'fuel' ? 'fuel_charge_status' : 'damage_status';
                if (in_array($op->$statusField, ['pending', null])) {
                    $op->$statusField = 'uncollectible';
                    $op->save();
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Charge marked as uncollectible.']);
    }
}
