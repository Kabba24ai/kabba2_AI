<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\VacationSummary;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\VacationSummary\StoreVacationRequest;
use App\Models\Iam\Personnel\VacationRequest;
use App\Models\Iam\Personnel\VacationRequestHour;
use Carbon\Carbon;

class VacationRequestStoreController extends BaseController
{
    public function __invoke(StoreVacationRequest $request)
    {
        $user = auth()->user();

        $hourOption = VacationRequestHour::findOrFail(
            $request->vacation_request_hour_id
        );

        // Calculate end date based on hours (8 hrs = 1 work day)
        $days = (int) ceil($hourOption->hours / 8);
        $startDate = Carbon::parse($request->start_date);
        $endDate = $startDate->copy()->addWeekdays($days - 1);

        $vacationRequest = VacationRequest::create([
            'employee_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'vacation_request_hour_id' => $hourOption->id,
            'request_type' => 'vacation',
            'status' => 'pending',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Vacation request submitted successfully',
            'data' => [
                'id' => $vacationRequest->id,
                'start_date' => $vacationRequest->start_date->toDateString(),
                'end_date' => $vacationRequest->end_date->toDateString(),
                'hours' => $hourOption->hours,
                'status' => $vacationRequest->status,
            ],
        ]);
    }
}
