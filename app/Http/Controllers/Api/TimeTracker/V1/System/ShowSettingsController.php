<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\System;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;
use App\Models\Configurations\TimeTrackerSetting;

class ShowSettingsController extends BaseController
{

    /**
     * Login
     * @group Admin App
     */
    public function __invoke()
    {
        $settings = TimeTrackerSetting::first();

        return response()->json([
            'success' => true,
              'message' => 'System settings Get successfully',
            'data' => $settings?->settings ?? null,
        ]);
    }
}
