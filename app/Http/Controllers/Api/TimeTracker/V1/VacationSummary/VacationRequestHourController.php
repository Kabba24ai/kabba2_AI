<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\VacationSummary;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\VacationRequestHour;


use Illuminate\Http\JsonResponse;

class VacationRequestHourController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $hours = VacationRequestHour::orderBy('hours')->get(['id', 'hours', 'name']);

        return response()->json([
            'success' => true,
            'message' => 'VacationRequestHour fetched successfully.',
            'data' => [
                'hours' => $hours,
            ],
        ]);
    }
}
