<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveTicketRequest;
use App\Models\Service\ServiceTicket;

class StoreController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(SaveTicketRequest $request)
    {
        $validated = $request->validated();

        $ticket = ServiceTicket::create(array_merge(
            collect($validated)->except(['personnel', 'order_id', 'rental_date'])->all(),
            $this->orderReferenceFields($validated),
            [
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
            ],
        ));

        $ticket->personnel()->sync($validated['personnel'] ?? []);

        // Completed/closed timestamps if the ticket is created directly in
        // one of those states (edge case, but keeps data consistent).
        $ticket->transitionTo($ticket->repair_status, $ticket->blocked_reason, $ticket->expected_action_date);

        flash('Service ticket ' . $ticket->fresh()->ticket_number . ' created.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
