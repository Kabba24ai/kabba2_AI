<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\TimeClock;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\TimeClock\ClockOutRequest;
use App\Http\Resources\Api\TimeTracker\V1\TimeClock\TimeEntryResource;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;
use App\Models\Iam\Personnel\TimeEntryBreak;

class LunchStartController extends BaseController
{
    public function __invoke(): JsonResponse
    {
       $user = auth()->user();
       $entry = $user->activeTimeEntry;

        if (!$entry || $entry->activeBreak) {
            return response()->json(['message' => 'Invalid state'], 422);
        }

        $entry->breaks()->create([
            'type' => 'lunch',
            'start_time' => Carbon::now(),
        ]);

        return response()->json(['success' => true]);
    }
}

