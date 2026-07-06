<?php

namespace App\Http\Controllers\Admin\ServiceManagement\ChargeLines;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveChargeLineRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SaveChargeLineRequest $request, ServiceTicket $ticket)
    {
        $data = $request->validated();

        $data['quantity'] ??= 1;
        $data['taxable']    = (bool) ($data['taxable'] ?? false);
        $data['billable']   = array_key_exists('billable', $data) && $data['billable'] !== null
            ? (bool) $data['billable']
            : $ticket->defaultBillable();

        $ticket->chargeLines()->create($data);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Charge line added to ' . $ticket->ticket_number . '.')->success();

        return $this->afterLineChange($request, $ticket, 'charge line added');
    }
}
