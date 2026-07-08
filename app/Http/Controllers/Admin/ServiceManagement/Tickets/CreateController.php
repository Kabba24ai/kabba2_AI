<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;

class CreateController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke()
    {
        // Rental-order intake only — the full workflow form lives on the
        // edit page; diagnosis and the rest happen on the workbench.
        return view('admin.service_management.tickets.create', $this->intakeFormData() + [
            'ticket' => new ServiceTicket(),
        ]);
    }
}
