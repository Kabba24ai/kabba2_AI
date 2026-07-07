<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\ManualResolutionAnswerRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.5 — Manual Resolution Scenario Foundation.
 *
 * Unlike Cancellation/Refund's sequential three-step wizard, Manual
 * Resolution has no branching between its two answers — category and
 * resolution are both known the moment the employee submits, so this is a
 * single-step action rather than a multi-request wizard.
 */
class ManualResolutionAnswerController extends Controller
{
    public function __invoke(string $uniqueId, ManualResolutionAnswerRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::recordAnswers($case->id, [
                'issue_category' => $validated['issue_category'],
                'selected_resolution' => $validated['selected_resolution'],
            ]);

            flash('Resolution recorded.')->success();

            return redirect()->route('admin.resolution-center.show', $uniqueId);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center manual-resolution answer error for case '.$uniqueId.': '.$e->getMessage());

            flash('Something went wrong while recording the resolution.')->error();

            return redirect()->back()->withInput();
        }
    }
}
