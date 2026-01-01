<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryCollection;
use App\Models\Iam\Personnel\TimeEntry;
use Illuminate\Http\JsonResponse;

class GetTodayTimeEntryController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entries = TimeEntry::where('employee_id', auth()->id())
            ->whereBetween('clock_in', [now()->startOfDay(), now()->endOfDay()])
            ->orderByDesc('clock_in')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Today time entries',
            'data' => new TimeEntryCollection($entries),
        ]);
    }
}
