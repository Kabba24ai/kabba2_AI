<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Notes;

use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketNote;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request, ServiceTicket $ticket, ServiceTicketNote $note)
    {
        abort_unless($note->service_ticket_id === $ticket->id, 404);

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:5000'],
        ]);

        $note->update($validated);

        flash('Note updated.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
