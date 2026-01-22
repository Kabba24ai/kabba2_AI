<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryResource;
use Illuminate\Http\JsonResponse;

class GetActiveTimeEntryController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entry = auth()->user()->activeTimeEntry;
        $activeBreak = $entry?->activeBreak;

        return response()->json([
            'success' => true,
            'message' => 'Active time entry',
            'data' => [
                'entry' => $entry ? new TimeEntryResource($entry) : null,
                'active_break' => $activeBreak ? [
                    'id' => $activeBreak->id,
                    'type' => $activeBreak->type,
                    'start_time' => $activeBreak->start_time,
                ] : null,
            ],
        ]);
    }
}
