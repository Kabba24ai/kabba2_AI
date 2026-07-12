<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\OrderProduct;
use App\Services\AlertLifecycleService;
use App\Services\BillingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UncollectibleController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $request->validate([
            'resolved_by' => ['required', 'integer', 'exists:users,id'],
        ]);

        if (! BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail()->status?->isOpen()) {
            return response()->json(['success' => false, 'message' => 'This charge is already closed.'], 422);
        }

        // Atomic: charge close + CA/OP status + lifecycle record succeed or fail
        // together. lockForUpdate serializes concurrent closes on the same charge.
        $closed = DB::transaction(function () use ($request, $chargeUniqueId) {
            $charge = BillingCharge::where('unique_id', $chargeUniqueId)->lockForUpdate()->firstOrFail();

            if (! $charge->status?->isOpen()) {
                return false; // a concurrent request already closed it
            }

            BillingEngine::markUncollectible($charge, (int) $request->resolved_by);

            $by = (int) $request->resolved_by;

            // Pure-CRM legacy account: lock the customer_accounts row + record.
            if ($charge->legacyCustomerAccount) {
                $ca = $charge->legacyCustomerAccount;
                if ($ca->fuel_alert_status === 'pending') {
                    AlertLifecycleService::transitionCustomerAccount((int) $ca->id, 'fuel', 'uncollectible', $by);
                } elseif ($ca->damage_alert_status === 'pending') {
                    AlertLifecycleService::transitionCustomerAccount((int) $ca->id, 'damage', 'uncollectible', $by);
                }
            }

            // Mobile-originated charges have no CA record — lock the order_products
            // row and transition it directly.
            if ($charge->order_product_id && ! $charge->customer_account_id) {
                $alertType = $charge->billing_charge_type?->value === 'fuel' ? 'fuel' : 'damage';
                AlertLifecycleService::transitionOrderProduct((int) $charge->order_product_id, $alertType, 'uncollectible', $by);
            }

            return true;
        });

        if (! $closed) {
            return response()->json(['success' => false, 'message' => 'This charge is already closed.'], 422);
        }

        return response()->json(['success' => true, 'message' => 'Charge marked as uncollectible.']);
    }
}
