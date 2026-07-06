<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ChargeLines;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketChargeLine;
use Illuminate\Http\Request;

class DestroyController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(Request $request, ServiceTicket $ticket, ServiceTicketChargeLine $chargeLine)
    {
        abort_unless($chargeLine->service_ticket_id === $ticket->id, 404);

        $chargeLine->delete();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Charge line removed.')->success();

        return $this->afterLineChange($request, $ticket, 'charge line removed');
    }
}
