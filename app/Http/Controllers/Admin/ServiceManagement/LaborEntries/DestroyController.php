<?php

namespace App\Http\Controllers\Admin\ServiceManagement\LaborEntries;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketLaborEntry;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(Request $request, ServiceTicket $ticket, ServiceTicketLaborEntry $laborEntry)
    {
        abort_unless($laborEntry->service_ticket_id === $ticket->id, 404);

        $laborEntry->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Labor entry removed.')->success();

        return $this->afterLineChange($request, $ticket, 'labor entry removed');
    }
}
