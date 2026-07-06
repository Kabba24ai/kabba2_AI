<?php

namespace App\Http\Controllers\Admin\ServiceManagement\LaborEntries;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveLaborEntryRequest;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketLaborEntry;

class UpdateController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SaveLaborEntryRequest $request, ServiceTicket $ticket, ServiceTicketLaborEntry $laborEntry)
    {
        abort_unless($laborEntry->service_ticket_id === $ticket->id, 404);

        $data = $request->validated();
        if (array_key_exists('billable', $data) && $data['billable'] !== null) {
            $data['billable'] = (bool) $data['billable'];
        } else {
            unset($data['billable']);
        }

        $laborEntry->fill($data);
        // Re-derive from the time range when hours were cleared
        if (empty($data['hours']) && $laborEntry->start_time && $laborEntry->end_time) {
            $laborEntry->hours = null;
        }
        $laborEntry->save();
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Labor entry updated.')->success();

        return $this->afterLineChange($request, $ticket, 'labor entry edited');
    }
}
