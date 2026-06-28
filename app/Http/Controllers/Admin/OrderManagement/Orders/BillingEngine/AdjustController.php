<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdjustController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $request->validate([
            'amount' => ['required', 'numeric'],
            'note'   => ['nullable', 'string', 'max:500'],
        ]);

        $charge = BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail();

        $before = $charge->amount;
        $delta  = (float) $request->amount;
        $after  = max(0, $before + $delta);

        $charge->amount = $after;

        if ($request->note) {
            $meta              = $charge->metadata ?? [];
            $meta['adjustments'][] = [
                'before'  => $before,
                'delta'   => $delta,
                'after'   => $after,
                'note'    => $request->note,
                'by'      => auth()->id(),
                'at'      => now()->toIso8601String(),
            ];
            $charge->metadata = $meta;
        }

        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge adjusted | unique_id={$charge->unique_id} | before={$before} | delta={$delta} | after={$after}"
        );

        return response()->json(['success' => true, 'message' => 'Fuel charge adjusted.']);
    }
}
