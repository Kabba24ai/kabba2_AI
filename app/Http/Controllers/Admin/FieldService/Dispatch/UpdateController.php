<?php

namespace App\Http\Controllers\Admin\FieldService\Dispatch;

use App\Enums\FieldService\FieldTicketEventType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldService\DispatchAssignmentRequest;
use App\Models\FieldService\FieldServiceTicket;
use App\Models\FieldService\FieldServiceTicketEvent;

class UpdateController extends Controller
{
    public function __invoke(DispatchAssignmentRequest $request, FieldServiceTicket $ticket)
    {
        if ($ticket->mission_status->isTerminal()) {
            flash('This mission is closed — dispatch details can no longer change.')->error();

            return redirect()->route('admin.field-service.tickets.show', $ticket);
        }

        $ticket->fill($request->validated())->save();

        FieldServiceTicketEvent::record(
            $ticket->id,
            FieldTicketEventType::DispatchUpdated,
            new: $ticket->technician
                ? 'Technician: ' . $ticket->technician->first_name . ' ' . $ticket->technician->last_name
                : 'No technician assigned',
        );

        flash('Dispatch assignment saved.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}
