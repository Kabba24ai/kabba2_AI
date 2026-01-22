<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Vacation;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\VacationRequest;
use Illuminate\Http\JsonResponse;

class ApproveVacationRequestController extends BaseController
{
    public function __invoke(string $id): JsonResponse
    {
        $request = VacationRequest::with('requestHour')->findOrFail($id);

        if ($request->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Request already processed',
            ], 422);
        }

        $request->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation request approved',
        ]);
    }
}
