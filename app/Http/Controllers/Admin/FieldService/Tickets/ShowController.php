<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;

/** The Field Operations Workbench — mission status, not shop repair stages. */
class ShowController extends Controller
{
    use BuildsFieldTicketFormData;

    public function __invoke(FieldServiceTicket $ticket)
    {
        $ticket->load([
            'order',
            'customer',
            'equipment.documentImages.media',
            'equipment.assignedProduct.media',
            'technician',
            'truck',
            'createdBy',
            'events.user',
            'notes.createdBy',
        ]);

        return view('admin.field_service.show', $this->fieldTicketFormData() + [
            'ticket' => $ticket,
        ]);
    }
}
