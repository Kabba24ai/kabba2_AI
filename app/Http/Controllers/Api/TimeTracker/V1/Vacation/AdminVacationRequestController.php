<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Vacation;

use App\Http\Controllers\Api\BaseController;
use App\Models\Iam\Personnel\VacationRequest;
use Illuminate\Http\Request;

use Illuminate\Http\JsonResponse;

class AdminVacationRequestController extends BaseController
{
    public function __invoke(Request $request): JsonResponse
    {
        $requests = VacationRequest::with([
                'employee:id,first_name,last_name',
                'requestHour:id,hours,name'
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($request) {
                return [
                    'id' => (string) $request->id,
                    'employee_id' => (string) $request->employee_id,
                    'employee_name' => trim(
                        $request->employee->first_name . ' ' . $request->employee->last_name
                    ),
                    'start_date' => $request->start_date->toDateString(),
                    'end_date' => $request->end_date->toDateString(),
                    'hours' => $request->requestHour?->hours ?? 0,
                     'denial_reason' => $request->denial_reason,
                    'status' => $request->status,
                    'created_at' => $request->created_at->toISOString(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
}
