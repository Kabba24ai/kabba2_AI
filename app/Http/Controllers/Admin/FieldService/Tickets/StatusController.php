<?php

namespace App\Http\Controllers\Admin\FieldService\Tickets;

use App\Enums\FieldService\FieldMissionStatus;
use App\Enums\FieldService\FieldOperationalExpectation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\FieldService\MissionStatusRequest;
use App\Models\FieldService\FieldServiceTicket;

class StatusController extends Controller
{
    public function __invoke(MissionStatusRequest $request, FieldServiceTicket $ticket)
    {
        $validated = $request->validated();
        $target    = FieldMissionStatus::from($validated['mission_status']);

        // Completing the assessment records what the technician found and
        // lets the office correct the initial expectation estimate.
        if ($target === FieldMissionStatus::AssessmentComplete) {
            $ticket->assessment_summary = $validated['assessment_summary'];
            if (!empty($validated['operational_expectation'])) {
                $ticket->operational_expectation = FieldOperationalExpectation::from($validated['operational_expectation']);
            }
            $ticket->save();
        }

        if (!$ticket->transitionTo($target, $validated['note'] ?? null)) {
            $blockers = $ticket->transitionBlockers($target);
            flash($blockers !== []
                ? implode(' ', $blockers)
                : 'That mission status change is not allowed from ' . $ticket->mission_status->label() . '.')->error();

            return redirect()->route('admin.field-service.tickets.show', $ticket);
        }

        // Saving the assessment immediately opens the mandatory Operational
        // Decision stage — the mission cannot close without an outcome.
        if ($target === FieldMissionStatus::AssessmentComplete) {
            $ticket->transitionTo(FieldMissionStatus::OperationalDecision);
            flash('Assessment recorded — select the Operational Decision to continue.')->success();

            return redirect()->route('admin.field-service.tickets.show', $ticket);
        }

        flash('Mission status updated to ' . $target->label() . '.')->success();

        return redirect()->route('admin.field-service.tickets.show', $ticket);
    }
}
