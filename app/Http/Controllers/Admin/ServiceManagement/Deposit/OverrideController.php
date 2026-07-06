<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Deposit;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class OverrideController extends Controller
{
    /** Manager override: skip the deposit gate, with a recorded reason. */
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'deposit_override_reason' => ['required', 'string', 'max:500'],
        ], [
            'deposit_override_reason.required' => 'A reason is required to override the parts deposit.',
        ]);

        $ticket->overrideDeposit($validated['deposit_override_reason']);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Parts deposit overridden on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
