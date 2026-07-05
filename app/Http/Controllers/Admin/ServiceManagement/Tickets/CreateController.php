<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class CreateController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke()
    {
        return view('admin.service_management.tickets.create', $this->ticketFormData() + [
            'ticket' => new ServiceTicket(),
        ]);
    }
}
