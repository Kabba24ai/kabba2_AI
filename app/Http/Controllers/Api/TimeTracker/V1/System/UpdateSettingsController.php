<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\System;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Api\BaseController;
use App\Models\Configurations\TimeTrackerSetting;
use Illuminate\Http\Request;

class UpdateSettingsController extends BaseController
{

    /**
     * Login
     * @group Admin App
     */
    public function __invoke(Request $request)
    {
        $settings = TimeTrackerSetting::firstOrCreate(
            [],
            ['settings' => []]
        );

        $settings->update([
            'settings' => $request->all(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'System settings updated successfully',
            'data' => $settings->settings,
        ]);
    }
}
