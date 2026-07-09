<?php

namespace App\Http\Controllers\Admin\Warranty\Cases;

use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;

/**
 * New Intake → Awaiting Diagnosis. Blocked for external customers until
 * the diagnostic fee is collected or waived.
 */
class CompleteIntakeController extends Controller
{
    public function __invoke(WarrantyCase $case)
    {
        if (!$case->transitionTo(WarrantyQueue::AwaitingDiagnosis)) {
            $blockers = $case->transitionBlockers(WarrantyQueue::AwaitingDiagnosis);
            flash($blockers !== []
                ? implode(' ', $blockers)
                : 'Intake can only be completed from the New Intake queue.')->error();

            return redirect()->route('admin.warranty.claims.show', $case);
        }

        flash('Intake complete — case is now awaiting diagnosis on '
            . ($case->serviceTicket?->ticket_number ?? 'its linked ticket') . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
