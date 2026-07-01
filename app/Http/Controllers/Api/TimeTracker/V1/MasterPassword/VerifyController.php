<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\MasterPassword;

use App\Http\Controllers\Api\BaseController;
use App\Http\Requests\Api\TimeTracker\V1\MasterPassword\VerifyRequest;
use App\Models\Configurations\Setting;
use Illuminate\Http\JsonResponse;

class VerifyController extends BaseController
{
    public function __invoke(VerifyRequest $request): JsonResponse
    {
        try {
            $setting = Setting::where('setting_name', 'master_password_entry')->first();

            if (!$setting) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server configuration error.',
                ], 500);
            }

            $stored = $setting->getDecryptedSettingValue();

            if (!$stored) {
                return response()->json([
                    'success' => false,
                    'message' => 'Server configuration error.',
                ], 500);
            }

            if (hash_equals($stored, $request->validated('master_password'))) {
                return response()->json([
                    'success' => true,
                    'message' => 'Unlocked successfully',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid master password',
            ], 403);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server configuration error.',
            ], 500);
        }
    }
}
