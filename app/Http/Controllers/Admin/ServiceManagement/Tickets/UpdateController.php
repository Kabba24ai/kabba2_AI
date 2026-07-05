<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\RepairStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceManagement\SaveTicketRequest;
use App\Models\Service\ServiceTicket;

class UpdateController extends Controller
{
    use BuildsTicketFormData;

    public function __invoke(SaveTicketRequest $request, ServiceTicket $ticket)
    {
        $validated = $request->validated();

        $ticket->fill(array_merge(
            collect($validated)->except(['personnel', 'order_id', 'rental_date', 'repair_status', 'blocked_reason', 'expected_action_date'])->all(),
            $this->orderReferenceFields($validated),
            ['updated_by' => auth()->id()],
        ));
        $ticket->save();

        // Status change goes through the transition rules (blocked context,
        // completed/closed timestamps, clearing blocked fields when active).
        $ticket->transitionTo(
            RepairStatus::from($validated['repair_status']),
            $validated['blocked_reason'] ?? null,
            $validated['expected_action_date'] ?? null,
        );

        $ticket->personnel()->sync($validated['personnel'] ?? []);

        flash('Service ticket ' . $ticket->ticket_number . ' updated.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
