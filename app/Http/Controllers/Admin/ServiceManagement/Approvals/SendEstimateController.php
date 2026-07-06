<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Approvals;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class SendEstimateController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->sendEstimate();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Estimate marked sent for ' . $ticket->ticket_number . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
