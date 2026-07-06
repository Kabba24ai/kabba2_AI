<?php

namespace App\Http\Controllers\Admin\ServiceManagement\Tickets;

use App\Enums\Service\RepairStatus;
use App\Http\Controllers\Controller;
use App\Models\Service\ServiceTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StatusController extends Controller
{
    /** Quick status transition from the ticket detail page. */
    public function __invoke(Request $request, ServiceTicket $ticket)
    {
        $blocked = RepairStatus::blocked();

        $validated = $request->validate([
            'repair_status'        => ['required', Rule::enum(RepairStatus::class)],
            'blocked_reason'       => [Rule::requiredIf(fn () => in_array($request->input('repair_status'), $blocked, true)), 'nullable', 'string', 'max:1000'],
            'expected_action_date' => [Rule::requiredIf(fn () => in_array($request->input('repair_status'), $blocked, true)), 'nullable', 'date'],
        ], [
            'blocked_reason.required'       => 'A blocked reason is required for waiting statuses.',
            'expected_action_date.required' => 'An expected action date is required for waiting statuses.',
        ]);

        $status = RepairStatus::from($validated['repair_status']);

        // Phase 2E.5 enforcement: repair work cannot begin or finish until
        // the authorization gate is satisfied. This is the backend guard —
        // API calls and future integrations cannot bypass it.
        if (!$ticket->canTransitionTo($status)) {
            $ticket->recordBlockedTransition($status);

            flash($ticket->blockedTransitionMessage())->error();

            return redirect()->route('admin.service-management.tickets.show', $ticket);
        }

        $ticket->transitionTo($status, $validated['blocked_reason'] ?? null, $validated['expected_action_date'] ?? null);
        $ticket->update(['updated_by' => auth()->id()]);

        flash('Ticket ' . $ticket->ticket_number . ' marked ' . $status->label() . '.')->success();

        return redirect()->route('admin.service-management.tickets.show', $ticket);
    }
}
