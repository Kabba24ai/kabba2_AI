<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Diagnostics;

use App\Enums\Service\ResponsibilityDecision;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DecideController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'responsibility_decision' => ['required', Rule::enum(ResponsibilityDecision::class), 'not_in:pending'],
        ]);

        if (!$ticket->diagnostic_status->allowsResponsibilityDecision()) {
            flash('Complete the diagnostic before setting a responsibility decision.')->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $ticket->decideResponsibility(ResponsibilityDecision::from($validated['responsibility_decision']));
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Responsibility set to ' . $ticket->responsibility_decision->label() . ' on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
