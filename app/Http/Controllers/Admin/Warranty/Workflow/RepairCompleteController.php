<?php

namespace App\Http\Controllers\Admin\Warranty\Workflow;

use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use Illuminate\Http\Request;

/**
 * ST-4: the repair happens on the linked Service Ticket; when it's done the
 * warranty admin moves the case to Awaiting Reimbursement (approved cases
 * only — a denied+proceed case has nothing to reimburse and closes here).
 */
class RepairCompleteController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        if ($case->queue !== WarrantyQueue::ApprovedForRepair) {
            return back()->with('error', 'This case is not in the repair stage.');
        }

        $validated = $request->validate(['notes' => ['nullable', 'string', 'max:1000']]);

        // Nothing to reimburse (fully customer-paid, e.g. after a denial) →
        // the case is economically done; close it. Otherwise track the OEM
        // reimbursement.
        $next = ($case->reimbursement_expected_amount ?? 0) > 0
            ? WarrantyQueue::AwaitingReimbursement
            : WarrantyQueue::Closed;

        $case->transitionTo($next, $validated['notes'] ?? null);

        flash($next === WarrantyQueue::Closed
            ? 'Repair complete — no OEM reimbursement expected; case closed.'
            : 'Repair complete — now awaiting OEM reimbursement.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
