<?php

namespace App\Http\Controllers\Admin\ServiceManagement\RepairAuthorization;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'repair_authorization_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (!$ticket->authorizeRepair($validated['repair_authorization_notes'] ?? null)) {
            flash('Repair cannot be authorized yet: ' . $ticket->authorizationBlockers()->implode(', ') . '.')->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $ticket->update(['updated_by' => auth()->id()]);

        flash('Repair authorized on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
