<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

class OtherStartController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entry = auth()->user()->activeTimeEntry;

        if (!$entry || $entry->activeBreak) {
            return response()->json([
                'message' => 'Invalid state'
            ], 422);
        }

        $entry->breaks()->create([
            'type' => 'other',
            'start_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Other break started'
        ]);
    }
}
