<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use App\Services\BillingEngine;
use Illuminate\Http\Request;

class ResolveController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $request->validate([
            'resolution_note' => ['required', 'string', 'max:500'],
            'resolved_by'     => ['required', 'integer', 'exists:users,id'],
        ]);

        $charge = BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail();

        if (! $charge->status?->isOpen()) {
            return response()->json(['success' => false, 'message' => 'This charge is already closed.'], 422);
        }

        BillingEngine::markResolved($charge, $request->resolution_note, (int) $request->resolved_by);

        if ($charge->legacyCustomerAccount) {
            $ca = $charge->legacyCustomerAccount;
            if ($ca->fuel_alert_status === 'pending') {
                $ca->fuel_alert_status = 'resolved';
                $ca->save();
            } elseif ($ca->damage_alert_status === 'pending') {
                $ca->damage_alert_status = 'resolved';
                $ca->save();
            }
        }

        return response()->json(['success' => true, 'message' => 'Charge marked as resolved.']);
    }
}
