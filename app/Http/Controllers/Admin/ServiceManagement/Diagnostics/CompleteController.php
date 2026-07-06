<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Diagnostics;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class CompleteController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->completeDiagnostic();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Diagnostic completed on ' . $ticket->ticket_number . '. You can now set the responsibility decision.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
