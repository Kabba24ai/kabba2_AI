<?php

namespace App\Http\Controllers\Admin\Warranty\Workflow;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Enums\Warranty\WarrantyQueue;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use App\Models\Warranty\WarrantyCaseEvent;
use Illuminate\Http\Request;

/** ST-4: submit a Ready-to-Submit case to the manufacturer. */
class SubmitController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        if ($case->queue !== WarrantyQueue::ReadyToSubmit) {
            return back()->with('error', 'This case is not ready to submit.');
        }

        $validated = $request->validate([
            'oem_submission_reference' => ['required', 'string', 'max:255'],
            'notes'                    => ['nullable', 'string', 'max:1000'],
        ]);

        $case->update(['oem_submission_reference' => $validated['oem_submission_reference']]);
        WarrantyCaseEvent::record($case->id, WarrantyCaseEventType::OemSubmitted,
            new: $validated['oem_submission_reference'], notes: $validated['notes'] ?? null);

        $case->transitionTo(WarrantyQueue::WaitingOnManufacturer, $validated['notes'] ?? null);

        flash('Submitted to manufacturer — reference ' . $validated['oem_submission_reference'] . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
