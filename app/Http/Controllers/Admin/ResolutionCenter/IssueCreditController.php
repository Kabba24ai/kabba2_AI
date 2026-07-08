<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\IssueCreditRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.3 — Customer Resolution Center Foundation.
 *
 * The one action in this phase that actually moves money — reachable only
 * via this explicit, separate endpoint (never triggered merely by viewing
 * a recommendation or opening a case). Gated by `customer_credit.grant`,
 * the same real permission Phase 3.1's Grant Credit dialog already
 * enforces — this is the same underlying capability, just reached from a
 * different screen, not a new one.
 */
class IssueCreditController extends Controller
{
    public function __invoke(string $uniqueId, IssueCreditRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::approveAndIssueCredit(
                caseId: $case->id,
                amount: (float) $validated['amount'],
                reason: $validated['reason'] ?: "Resolution Center — Order #{$case->order->order_number}",
                responsibleUserId: auth()->id(),
                notes: $validated['notes'] ?? null,
            );

            flash('Store credit issued.')->success();

            return redirect()->route('admin.resolution-center.show', $uniqueId);
        } catch (\InvalidArgumentException $e) {
            flash($e->getMessage())->error();

            return redirect()->back()->withInput();
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center credit issuance error for case '.$uniqueId.': '.$e->getMessage());

            flash('Something went wrong while issuing store credit.')->error();

            return redirect()->back()->withInput();
        }
    }
}
