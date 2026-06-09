<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Customers\CustomerCallNeeded;

class CallNeededClearController extends Controller
{
    public function __invoke($id)
{
    $call = CustomerCallNeeded::findOrFail($id);

    $call->update([
        'status' => 'clear',
    ]);

    $note = $call->customer->notes()
        ->where('customer_call_needed_id', $call->id)
        ->latest('id')
        ->first();

    if ($note) {
        $note->update([
            'description' => $note->description .
                "\n\n-- Call Completed (" . now()->format('m/d/Y h:i A') . ")",
        ]);
    }

    return response()->json([
        'success' => true,
        'message' => 'Call reminder cleared successfully.',
    ]);
}
}