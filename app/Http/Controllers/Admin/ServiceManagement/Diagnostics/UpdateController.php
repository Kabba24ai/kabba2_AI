<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Diagnostics;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveDiagnosticRequest;
use App\Models\Service\ServiceTicket;

class UpdateController extends Controller
{
    /** Save diagnostic findings, estimates, and fee state — no payments. */
    public function __invoke(SaveDiagnosticRequest $request, ServiceTicket $ticket)
    {
        $ticket->fill($request->validated());
        $ticket->updated_by = auth()->id();
        $ticket->save();

        flash('Diagnostic details saved for ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
