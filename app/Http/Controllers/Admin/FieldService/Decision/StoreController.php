<?php

namespace App\Http\Controllers\Admin\FieldService\Decision;

use App\Enums\FieldService\FieldOperationalOutcome;
use App\Http\Controllers\Controller;
use App\Models\FieldService\FieldServiceTicket;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Records the Operational Decision — the mandatory branching point between
 * field assessment and execution. One outcome, chosen once, permanent.
 */
class StoreController extends Controller
{
    public function __invoke(Request $request, FieldServiceTicket $ticket)
    {
        $validated = $request->validate([
            'operational_outcome' => ['required', Rule::enum(FieldOperationalOutcome::class)],
        ], [
            'operational_outcome.required' => 'Select one operational outcome to continue.',
        ]);

        $outcome = FieldOperationalOutcome::from($validated['operational_outcome']);

        if (!$ticket->recordOperationalDecision($outcome)) {
            flash($ticket->operational_outcome
                ? 'The Operational Decision is already recorded as ' . $ticket->operational_outcome->label() . ' and cannot be changed.'
                : 'The Operational Decision can only be made after the field assessment is complete.')->error();

            return redirect()->route('admin.field-service.tickets.show', $ticket);
        }

        flash('Operational Decision recorded: ' . $outcome->label() . '.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}
