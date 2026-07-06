<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class DeclineController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'approval_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->declineEstimate($validated['approval_notes'] ?? null);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Estimate declined on ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
