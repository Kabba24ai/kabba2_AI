<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\OperationsAssignRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.6 — Customer Resolution Operations Center.
 * The mission's "Assign Case" / "Reassign Case" manager function.
 */
class OperationsAssignController extends Controller
{
    public function __invoke(string $uniqueId, OperationsAssignRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::assignCase(
                caseId: $case->id,
                assignedToUserId: $validated['assigned_to_user_id'] ?? null,
                performedByUserId: auth()->id(),
            );

            return response()->json(['success' => true, 'message' => 'Case assignment updated.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center assign error for case '.$uniqueId.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Something went wrong while updating the assignment.'], 422);
        }
    }
}
