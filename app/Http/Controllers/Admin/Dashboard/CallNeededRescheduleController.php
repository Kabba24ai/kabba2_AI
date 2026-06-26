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

        $originalDueDate  = $call->due_date ? $call->due_date->format('M j, Y g:i A') : null;
        $newDate          = Carbon::parse($request->follow_up_at);
        $newDateFormatted = $newDate->format('M j, Y g:i A');
        $statusLabel      = ucwords(str_replace('_', ' ', $request->call_status));

        // Build a rich audit note so history captures the full context
        $activityNote = 'Rescheduled';
        if ($originalDueDate) {
            $activityNote .= " from {$originalDueDate}";
        }
        $activityNote .= " to {$newDateFormatted}. Action: {$statusLabel}.";
        if ($request->filled('completion_note')) {
            $activityNote .= " Notes: {$request->completion_note}";
        }
        if ($request->filled('follow_up_note')) {
            $activityNote .= " Reminder note: {$request->follow_up_note}";
        }

        CustomerCallNeededActivity::create([
            'customer_call_needed_id' => $call->id,
            'status'                  => 'rescheduled',
            'notes'                   => $activityNote,
            'follow_up_date'          => $newDate->toDateString(),
            'created_by'              => auth()->id(),
        ]);

        // Move due_date to the new date and clear follow_up_at so the task
        // is immediately visible in the active list sorted under the new date.
        $call->update([
            'due_date'       => $newDate,
            'follow_up_at'   => null,
            'follow_up_note' => $request->follow_up_note,
            'rescheduled_by' => auth()->id(),
            'rescheduled_at' => now(),
        ]);

        if ($call->customer) {
            $description = "Call rescheduled to {$newDateFormatted}. Action: {$statusLabel}.";
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

        return response()->json([
            'success' => true,
            'message' => "Call rescheduled to {$newDateFormatted}.",
        ]);
    }
}
