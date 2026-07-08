<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.6 — the mission's "Close Case" manager quick-action. A thin,
 * no-request-body wrapper around the existing `markOutcome()` — see
 * ResolutionCenterService::closeCase().
 */
class OperationsCloseController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::closeCase($case->id);

            return response()->json(['success' => true, 'message' => 'Case closed.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center close error for case '.$uniqueId.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Something went wrong while closing the case.'], 422);
        }
    }
}
