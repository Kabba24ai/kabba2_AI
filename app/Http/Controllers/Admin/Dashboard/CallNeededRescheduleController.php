<?php

namespace App\Http\Controllers\Admin\Dashboard;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Customers\CustomerCallNeeded;
use App\Models\Customers\CustomerCallNeededActivity;

class CallNeededRescheduleController extends Controller
{
    public function __invoke(Request $request, $id)
    {
        $request->validate([
            'call_status'     => ['required', 'string'],
            'completion_note' => ['nullable', 'string', 'max:5000'],
            'follow_up_at'    => ['required', 'date', 'after:now'],
            'follow_up_note'  => ['nullable', 'string', 'max:5000'],
        ]);

        $call = CustomerCallNeeded::findOrFail($id);

        CustomerCallNeededActivity::create([
            'customer_call_needed_id' => $call->id,
            'status'                  => $request->call_status,
            'notes'                   => $request->completion_note,
            'follow_up_date'          => $request->follow_up_at,
            'created_by'              => auth()->id(),
        ]);

        $call->update([
            'follow_up_at'   => $request->follow_up_at,
            'follow_up_note' => $request->follow_up_note,
            'rescheduled_by' => auth()->id(),
            'rescheduled_at' => now(),
        ]);

        if ($call->customer) {
            $statusLabel       = ucwords(str_replace('_', ' ', $request->call_status));
            $followUpFormatted = Carbon::parse($request->follow_up_at)->format('M j, Y g:i A');
            $description       = "Call rescheduled. Follow-up at: {$followUpFormatted}. Action: {$statusLabel}.";

            if ($request->filled('completion_note')) {
                $description .= " Notes: {$request->completion_note}";
            }

            if ($request->filled('follow_up_note')) {
                $description .= " Follow-up reminder: {$request->follow_up_note}";
            }

            $call->customer->notes()->create([
                'customer_call_needed_id' => $call->id,
                'description'             => $description,
                'created_by'              => auth()->id(),
            ]);
        }

        $followUpFormatted = Carbon::parse($request->follow_up_at)->format('M j, Y g:i A');

        return response()->json([
            'success' => true,
            'message' => "Call rescheduled. It will reappear on {$followUpFormatted}.",
        ]);
    }
}
