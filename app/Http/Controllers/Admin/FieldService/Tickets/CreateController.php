<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;

class CreateController extends Controller
{
    use BuildsFieldTicketFormData;

    public function __invoke()
    {
        // Ticket creation answers one question: what does the technician
        // need before leaving the shop? The field outcome comes later.
        return view('admin.field_service.create', $this->fieldTicketFormData() + [
            'ticket' => new FieldServiceTicket(),
        ]);
    }
}
