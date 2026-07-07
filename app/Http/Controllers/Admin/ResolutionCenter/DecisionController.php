<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\DecisionRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * `manager_override_reason` is only persisted when the authenticated user
 * holds `resolution_center.override` — enforced here, not just hidden in
 * the UI, since a UI-only guard is trivially bypassable by posting the
 * field directly.
 */
class DecisionController extends Controller
{
    public function __invoke(string $uniqueId, DecisionRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            $hasOverridePermission = auth()->user()?->can('resolution_center.override') ?? false;

            ResolutionCenterService::recordDecision(
                caseId: $case->id,
                employeeDecision: $validated['employee_decision'],
                employeeDecisionDetail: $validated['employee_decision_detail'] ?? null,
                managerOverrideUserId: $hasOverridePermission && ! empty($validated['manager_override_reason']) ? auth()->id() : null,
                managerOverrideReason: $hasOverridePermission ? ($validated['manager_override_reason'] ?? null) : null,
                notes: $validated['notes'] ?? null,
            );

            if (! empty($validated['outcome'])) {
                ResolutionCenterService::markOutcome($case->id, $validated['outcome']);
            }

            flash('Decision recorded.')->success();

            return redirect()->route('admin.resolution-center.show', $uniqueId);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center decision error for case '.$uniqueId.': '.$e->getMessage());

            flash('Something went wrong while recording the decision.')->error();

            return redirect()->back()->withInput();
        }
    }
}
