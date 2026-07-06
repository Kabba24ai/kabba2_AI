<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Parts;

use App\Http\Controllers\Admin\ServiceManagement\HandlesLineItemRequests;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SavePartRequest;
use App\Models\Service\ServiceTicket;
use App\Models\Service\ServiceTicketPart;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    use HandlesLineItemRequests;

    public function __invoke(SavePartRequest $request, ServiceTicket $ticket, ServiceTicketPart $part)
    {
        abort_unless($part->service_ticket_id === $ticket->id, 404);

        $data = $request->validated();
        foreach (['warranty_eligible', 'billable'] as $flag) {
            if ($request->has($flag)) {
                $data[$flag] = $request->boolean($flag);
            } else {
                unset($data[$flag]);
            }
        }

        $part->update($data);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Part updated.')->success();

        return $this->afterLineChange($request, $ticket, 'part edited');
    }
}
