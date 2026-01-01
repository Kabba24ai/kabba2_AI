<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Vacation;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\VacationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DenyVacationRequestController extends BaseController
{
    public function __invoke(Request $request, string $id): JsonResponse
    {
        $vacationRequest = VacationRequest::findOrFail($id);

        $vacationRequest->update([
            'status' => 'denied',
            'denial_reason' => $request->input('reason'),
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation request denied',
        ]);
    }
}
