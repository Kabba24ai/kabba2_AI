<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\OperationsMarkWaitingRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

class OperationsMarkWaitingController extends Controller
{
    public function __invoke(string $uniqueId, OperationsMarkWaitingRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::markWaiting(
                caseId: $case->id,
                waitingOn: $validated['waiting_on'],
                performedByUserId: auth()->id(),
                note: $validated['note'] ?? null,
            );

            return response()->json(['success' => true, 'message' => 'Case marked as waiting.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center mark-waiting error for case '.$uniqueId.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Something went wrong while marking the case as waiting.'], 422);
        }
    }
}
