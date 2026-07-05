<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class EditController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(ServiceTicket $ticket)
    {
        $ticket->load('personnel');

        return view('admin.service_management.tickets.edit', $this->ticketFormData() + [
            'ticket' => $ticket,
        ]);
    }
}
