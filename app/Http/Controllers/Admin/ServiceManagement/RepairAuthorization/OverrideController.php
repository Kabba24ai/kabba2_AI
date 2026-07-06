<?php

namespace App\Http\Controllers\Admin\ServiceManagement\RepairAuthorization;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class OverrideController extends Controller
{
    /**
     * Manager override of the repair authorization gate. Immediately
     * satisfies the authorization requirement with a full audit trail.
     */
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'authorization_override_reason' => ['required', 'string', 'max:500'],
        ], [
            'authorization_override_reason.required' => 'A reason is required to override repair authorization.',
        ]);

        $ticket->overrideAuthorization($validated['authorization_override_reason']);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Repair authorization overridden on ' . $ticket->ticket_number . '. Repair work may proceed.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
