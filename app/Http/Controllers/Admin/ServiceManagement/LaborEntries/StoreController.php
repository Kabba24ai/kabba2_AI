<?php

namespace App\Http\Controllers\Admin\ServiceManagement\LaborEntries;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveLaborEntryRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SaveLaborEntryRequest $request, ServiceTicket $ticket)
    {
        $data = $request->validated();

        // Billable defaults follow the ticket's financial responsibility
        // unless the form said otherwise.
        $data['billable'] = array_key_exists('billable', $data) && $data['billable'] !== null
            ? (bool) $data['billable']
            : $ticket->defaultBillable();

        $ticket->laborEntries()->create($data);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Labor entry added to ' . $ticket->ticket_number . '.')->success();

        return $this->afterLineChange($request, $ticket, 'labor entry added');
    }
}
