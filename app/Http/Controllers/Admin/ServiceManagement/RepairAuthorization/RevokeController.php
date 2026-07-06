<?php

namespace App\Http\Controllers\Admin\ServiceManagement\RepairAuthorization;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class RevokeController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'repair_authorization_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->revokeRepairAuthorization($validated['repair_authorization_notes'] ?? null);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Repair authorization revoked on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
