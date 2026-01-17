<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\JsonResponse;

class OtherEndController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        $entry = auth()->user()->activeTimeEntry;
        $break = $entry?->activeBreak;

        if (!$break || $break->type !== 'other') {
            return response()->json([
                'message' => 'No active other break'
            ], 422);
        }

        $break->update([
            'end_time' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Other break ended'
        ]);
    }
}
