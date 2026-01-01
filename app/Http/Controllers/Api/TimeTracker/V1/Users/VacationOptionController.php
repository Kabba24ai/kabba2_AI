<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Users;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\Users\EmployeeResource;
use App\Models\Iam\Personnel\User;
use App\Models\Iam\Personnel\VacationHour;
use App\Models\Iam\Personnel\VacationDay;


use Illuminate\Http\JsonResponse;

class VacationOptionController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $hours = VacationHour::orderBy('hours')->get(['id', 'hours', 'name']);

        $days = VacationDay::orderBy('day_number')->get(['id', 'day_number', 'name']);


        return response()->json([
            'success' => true,
            'message' => 'VacationHour & VacationDay fetched successfully.',
            'data' => [
                'hours' => $hours,
                'days' => $days,
            ],
        ]);
    }
}
