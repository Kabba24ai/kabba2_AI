<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Diagnostics;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class StartController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->startDiagnostic();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Diagnostic started on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
