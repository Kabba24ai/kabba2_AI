<?php

namespace App\Http\Controllers\Admin\Warranty\Fee;

use App\Enums\Warranty\WarrantyCaseEventType;
use App\Http\Controllers\Controller;
use App\Models\Warranty\WarrantyCase;
use App\Models\Warranty\WarrantyCaseEvent;
use Illuminate\Http\Request;

/**
 * Diagnostic fee state recording (Phase 1): mark collected or waived.
 * No payment processing — money moves through existing channels; this
 * records that it happened.
 */
class UpdateController extends Controller
{
    public function __invoke(Request $request, WarrantyCase $case)
    {
        $validated = $request->validate([
            'action' => ['required', 'in:collected,waived,reset'],
        ]);

        if (!$case->feeApplies()) {
            flash('Internal warranty cases have no diagnostic fee.')->error();

            return redirect()->route('admin.warranty.claims.show', $case);
        }

        $case->forceFill(match ($validated['action']) {
            'collected' => ['diagnostic_fee_collected_at' => now(), 'diagnostic_fee_waived_by' => null],
            'waived'    => ['diagnostic_fee_waived_by' => auth()->id(), 'diagnostic_fee_collected_at' => null],
            'reset'     => ['diagnostic_fee_collected_at' => null, 'diagnostic_fee_waived_by' => null],
        })->save();

        WarrantyCaseEvent::record(
            $case->id,
            WarrantyCaseEventType::FeeUpdated,
            new: $case->fresh()->feeStatusLabel(),
        );

        flash('Diagnostic fee updated: ' . $case->fresh()->feeStatusLabel() . '.')->success();

        return redirect()->route('admin.warranty.claims.show', $case);
    }
}
