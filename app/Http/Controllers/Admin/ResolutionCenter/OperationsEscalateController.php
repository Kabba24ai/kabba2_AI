<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\OperationsEscalateRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

class OperationsEscalateController extends Controller
{
    public function __invoke(string $uniqueId, OperationsEscalateRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::escalate($case->id, auth()->id(), $validated['reason'] ?? null);

            return response()->json(['success' => true, 'message' => 'Case escalated.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center escalate error for case '.$uniqueId.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Something went wrong while escalating the case.'], 422);
        }
    }
}
