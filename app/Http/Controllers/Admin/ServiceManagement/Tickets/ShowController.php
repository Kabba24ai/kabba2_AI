<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class ShowController extends Controller
{
    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->load(['personnel', 'equipment', 'order', 'customer', 'createdBy']);

        return view('admin.service_management.tickets.show', compact('ticket'));
    }
}
