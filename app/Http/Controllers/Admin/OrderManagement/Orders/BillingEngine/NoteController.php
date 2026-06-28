<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\BillingEngine;

use App\Http\Controllers\Controller;
use App\Models\Orders\BillingCharge;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NoteController extends Controller
{
    public function __invoke(Request $request, string $chargeUniqueId)
    {
        $request->validate([
            'note' => ['required', 'string', 'max:1000'],
        ]);

        $charge = BillingCharge::where('unique_id', $chargeUniqueId)->firstOrFail();

        $charge->notes = $request->note;
        $charge->save();

        Log::channel('billing_engine')->info(
            "BillingEngine charge note updated | unique_id={$charge->unique_id}"
        );

        return response()->json(['success' => true, 'message' => 'Note saved.']);
    }
}
