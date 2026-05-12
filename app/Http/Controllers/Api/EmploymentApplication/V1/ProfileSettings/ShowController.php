<?php

namespace App\Http\Controllers\Api\EmploymentApplication\V1\ProfileSettings;

use App\Http\Controllers\Api\BaseController;
use App\Helpers\ConfigurationHelper;
use App\Http\Resources\Api\ProfileSettings\ProfileSettingsResource;
use Illuminate\Http\JsonResponse;

class ShowController extends BaseController
{
    public function __invoke(): JsonResponse
    {
        // Get all profile settings
        $settings = ConfigurationHelper::getSettings('Website Management Branding');

        return response()->json([
            'success' => true,
            'message' => 'Profile settings fetched successfully.',
            'data' => new ProfileSettingsResource($settings)
        ]);
    }
}