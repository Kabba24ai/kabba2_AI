<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Diagnostics;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceResponsibilityDecision;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class DecideController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        // The decision must be an ACTIVE master record — deactivated options
        // are never offered for a new selection.
        $validated = $request->validate([
            'responsibility_decision' => [
                'required',
                'exists:service_responsibility_decisions,id,is_active,1',
            ],
        ]);

        if (!$ticket->diagnostic_status->allowsResponsibilityDecision()) {
            flash('Complete the diagnostic before setting a responsibility decision.')->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $decision = ServiceResponsibilityDecision::findOrFail($validated['responsibility_decision']);

        $ticket->decideResponsibility($decision);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Responsibility set to ' . $decision->name . ' on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
