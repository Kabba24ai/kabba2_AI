<?php

namespace App\Http\Controllers\Admin\Warranty\Workflow;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use App\Models\Warranty\WarrantyCaseEvent;
use Illuminate\Http\Request;

/**
 * ST-4: record the OEM reimbursement and close the case. State-only — the
 * Warranty module posts no billing/payment records (by design); this is the
 * administrative record that the manufacturer paid.
 */
class ReimbursementController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        if ($case->queue !== WarrantyQueue::AwaitingReimbursement) {
            return back()->with('error', 'This case is not awaiting reimbursement.');
        }

        $validated = $request->validate([
            'reimbursement_received_amount' => ['required', 'numeric', 'min:0'],
            'reimbursement_received_at'     => ['required', 'date'],
            'notes'                         => ['nullable', 'string', 'max:1000'],
        ]);

        $case->update([
            'reimbursement_received_amount' => (float) $validated['reimbursement_received_amount'],
            'reimbursement_received_at'     => $validated['reimbursement_received_at'],
        ]);

        WarrantyCaseEvent::record($case->id, WarrantyCaseEventType::ReimbursementRecorded,
            new: '$' . number_format((float) $validated['reimbursement_received_amount'], 2),
            notes: $validated['notes'] ?? null);

        $case->transitionTo(WarrantyQueue::Closed, $validated['notes'] ?? null);

        flash('Reimbursement recorded — case closed.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
