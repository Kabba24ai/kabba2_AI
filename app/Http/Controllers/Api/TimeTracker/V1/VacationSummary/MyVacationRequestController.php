<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\VacationSummary;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\VacationRequest;
use Illuminate\Http\JsonResponse;

class MyVacationRequestController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $user = auth()->user();

        $requests = VacationRequest::with('requestHour')
            ->where('employee_id', $user->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => (string) $request->id,
                    'employee_id' => (string) $request->employee_id,
                    'start_date' => $request->start_date->toDateString(),
                    'end_date' => $request->end_date->toDateString(),
                    'vacation_request_hour_id' => $request->vacation_request_hour_id,
                    'hours' => $request->requestHour?->hours ?? 0,
                    'denial_reason' => $request->denial_reason,
                    'status' => $request->status,
                    'created_at' => $request->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'message' => 'My vacation requests fetched successfully',
            'data' => $requests,
        ]);
    }
}
