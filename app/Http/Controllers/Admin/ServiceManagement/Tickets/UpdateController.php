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

        $newStatus = isset($validated['repair_status'])
            ? RepairStatus::from($validated['repair_status'])
            : $ticket->repair_status;

        // Phase 2E.5 enforcement: the edit form is a backend entry point too —
        // reject the whole update if it tries to move into repair execution
        // without authorization.
        if ($newStatus !== $ticket->repair_status && !$ticket->canTransitionTo($newStatus)) {
            $ticket->recordBlockedTransition($newStatus);

            flash($ticket->blockedTransitionMessage())->error();

            return back()->withInput();
        }

        $ticket->fill(array_merge(
            collect($validated)->except(['personnel', 'team_leader_id', 'intake', 'order_id', 'rental_date', 'repair_status', 'blocked_reason', 'expected_action_date'])->all(),
            $this->orderReferenceFields($validated),
            ['updated_by' => auth()->id()],
        ));
        $ticket->save();

        // Status change goes through the transition rules (blocked context,
        // completed/closed timestamps, clearing blocked fields when active).
        $ticket->transitionTo(
            $newStatus,
            $validated['blocked_reason'] ?? null,
            $validated['expected_action_date'] ?? null,
        );

        $ticket->syncPersonnel($validated['personnel'] ?? []);

        flash('Service ticket ' . $ticket->ticket_number . ' updated.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
