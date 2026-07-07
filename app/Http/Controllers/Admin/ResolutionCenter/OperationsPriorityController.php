<?php

namespace App\Http\Controllers\Admin\ResolutionCenter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ResolutionCenter\OperationsPriorityRequest;
use App\Models\Customers\ResolutionCase;
use App\Services\ResolutionCenterService;
use Illuminate\Support\Facades\Log;

class OperationsPriorityController extends Controller
{
    public function __invoke(string $uniqueId, OperationsPriorityRequest $request)
    {
        $validated = $request->validated();

        try {
            $case = ResolutionCase::where('unique_id', $uniqueId)->firstOrFail();

            ResolutionCenterService::setPriority($case->id, $validated['priority'], auth()->id());

            return response()->json(['success' => true, 'message' => 'Priority updated.']);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Resolution Center priority error for case '.$uniqueId.': '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Something went wrong while updating priority.'], 422);
        }
    }
}
