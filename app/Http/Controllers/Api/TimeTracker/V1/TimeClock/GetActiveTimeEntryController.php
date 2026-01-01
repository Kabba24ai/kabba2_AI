<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryResource;
use Illuminate\Http\JsonResponse;

class GetActiveTimeEntryController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Active time entry',
            'data' => auth()->user()->activeTimeEntry
                ? new TimeEntryResource(auth()->user()->activeTimeEntry)
                : null,
        ]);
    }
}
