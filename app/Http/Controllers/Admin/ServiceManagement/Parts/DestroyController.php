<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Parts;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketPart;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(Request $request, ServiceTicket $ticket, ServiceTicketPart $part)
    {
        abort_unless($part->service_ticket_id === $ticket->id, 404);

        $part->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Part removed.')->success();

        return $this->afterLineChange($request, $ticket, 'part removed');
    }
}
