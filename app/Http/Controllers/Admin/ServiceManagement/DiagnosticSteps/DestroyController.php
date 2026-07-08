<?php

namespace App\Http\Controllers\Admin\ServiceManagement\DiagnosticSteps;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketDiagnosticStep;

class DestroyController extends Controller
{
    public function __invoke(ServiceTicket $ticket, ServiceTicketDiagnosticStep $step)
    {
        abort_unless($step->service_ticket_id === $ticket->id, 404);

        $step->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Diagnostic step removed.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
