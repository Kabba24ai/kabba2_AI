<?php

namespace App\Http\Controllers\Admin\Warranty\Workflow;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyOemDecision;
use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use App\Models\Warranty\WarrantyCaseEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * ST-4: record the manufacturer's decision. Approved → straight to repair;
 * Partial/Denied → the customer decides on the balance / a paid repair.
 * The approved amount becomes the expected reimbursement.
 */
class OemDecisionController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        if ($case->queue !== WarrantyQueue::WaitingOnManufacturer) {
            return back()->with('error', 'This case is not awaiting a manufacturer decision.');
        }

        $validated = $request->validate([
            'oem_decision'        => ['required', Rule::enum(WarrantyOemDecision::class)],
            'oem_approved_amount' => ['nullable', 'numeric', 'min:0', Rule::requiredIf(fn () => in_array($request->input('oem_decision'), ['approved', 'partial'], true))],
            'notes'               => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = WarrantyOemDecision::from($validated['oem_decision']);
        $approved = $decision === WarrantyOemDecision::Denied ? null : (float) $validated['oem_approved_amount'];

        $case->update([
            'oem_decision'                  => $decision->value,
            'oem_approved_amount'           => $approved,
            'reimbursement_expected_amount' => $approved, // what we expect back from OEM
        ]);

        WarrantyCaseEvent::record($case->id, WarrantyCaseEventType::OemDecision,
            new: $decision->label(),
            notes: $validated['notes'] ?? null,
            metadata: ['approved_amount' => $approved]);

        // Full approval skips the customer decision; partial/denied route
        // through it (the queue machinery permits both edges).
        $next = $decision === WarrantyOemDecision::Approved
            ? WarrantyQueue::ApprovedForRepair
            : WarrantyQueue::AwaitingCustomerDecision;

        $case->transitionTo($next, $validated['notes'] ?? null);

        flash('OEM decision recorded: ' . $decision->label() . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
