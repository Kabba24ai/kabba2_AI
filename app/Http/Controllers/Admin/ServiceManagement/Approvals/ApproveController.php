<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;

class ApproveController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $validated = $request->validate([
            'approved_by_customer_name' => ['nullable', 'string', 'max:255'],
            'approval_notes'            => ['nullable', 'string', 'max:2000'],
        ]);

        $ticket->approveEstimate($validated['approved_by_customer_name'] ?? null, $validated['approval_notes'] ?? null);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Approval recorded for ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
