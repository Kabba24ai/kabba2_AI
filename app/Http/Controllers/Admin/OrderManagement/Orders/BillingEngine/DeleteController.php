<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DeleteController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $charge = BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail();

        if ($charge->billing_charge_type?->value !== 'extension') {
            return response()->json(['success' => false, 'message' => 'Only extension charges can be deleted.'], 422);
        }

        // Soft-delete the linked child extension Order if present
        if ($charge->childOrder) {
            $charge->childOrder->delete();
        }

        $charge->delete();

        Log::channel('billing_engine')->info(
            "Extension BillingCharge deleted | unique_id={$charge->unique_id} | by=" . auth()->id()
        );

        return response()->json(['success' => true, 'message' => 'Extension charge deleted.']);
    }
}
