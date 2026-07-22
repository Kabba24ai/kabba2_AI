<?php

namespace App\Http\Controllers\Admin\Warranty\Workflow;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyCustomerDecision;
use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use App\Models\Warranty\WarrantyCaseEvent;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** ST-4: record the customer's decision after a partial/denied OEM outcome. */
class CustomerDecisionController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        if ($case->queue !== WarrantyQueue::AwaitingCustomerDecision) {
            return back()->with('error', 'This case is not awaiting a customer decision.');
        }

        $validated = $request->validate([
            'customer_decision' => ['required', Rule::enum(WarrantyCustomerDecision::class)],
            'notes'             => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = WarrantyCustomerDecision::from($validated['customer_decision']);
        $case->update(['customer_decision' => $decision->value]);

        WarrantyCaseEvent::record($case->id, WarrantyCaseEventType::CustomerDecision,
            new: $decision->label(), notes: $validated['notes'] ?? null);

        $next = $decision === WarrantyCustomerDecision::Proceed
            ? WarrantyQueue::ApprovedForRepair
            : WarrantyQueue::Closed;

        $case->transitionTo($next, $validated['notes'] ?? null);

        flash('Customer decision recorded: ' . $decision->label() . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
