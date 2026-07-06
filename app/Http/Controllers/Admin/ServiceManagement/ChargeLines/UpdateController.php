<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ChargeLines;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveChargeLineRequest;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketChargeLine;

class UpdateController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SaveChargeLineRequest $request, ServiceTicket $ticket, ServiceTicketChargeLine $chargeLine)
    {
        abort_unless($chargeLine->service_ticket_id === $ticket->id, 404);

        $data = $request->validated();
        foreach (['billable', 'taxable'] as $flag) {
            if (array_key_exists($flag, $data) && $data[$flag] !== null) {
                $data[$flag] = (bool) $data[$flag];
            } else {
                unset($data[$flag]);
            }
        }

        $chargeLine->update($data);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Charge line updated.')->success();

        return $this->afterLineChange($request, $ticket, 'charge line edited');
    }
}
