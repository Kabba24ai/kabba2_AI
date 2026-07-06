<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Parts;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SavePartRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SavePartRequest $request, ServiceTicket $ticket)
    {
        $data = $request->validated();
        $data['quantity']          ??= 1;
        $data['warranty_eligible']   = (bool) ($data['warranty_eligible'] ?? false);
        $data['billable']            = array_key_exists('billable', $data) && $data['billable'] !== null
            ? (bool) $data['billable']
            : $ticket->defaultBillable();

        $ticket->partsUsed()->create($data);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Part recorded on ' . $ticket->ticket_number . '.')->success();

        return $this->afterLineChange($request, $ticket, 'part added');
    }
}
