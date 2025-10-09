<?php

namespace App\Http\Controllers\Admin\Configurations;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\VerifyMasterRequest;

// Models
use App\Models\Configurations\Setting;

class VerifyMasterController extends Controller
{
    public function __invoke(VerifyMasterRequest $request)
    {
        $validated = $request->validated();
        session()->forget('master_verified');
        try {
            $master = Setting::where('setting_name', 'master_password_entry')->first();
            $decryptedMaster = $master ? $master->getDecryptedSettingValue() : null;

            if ($decryptedMaster === $validated['password']) {
                // Fetch the actual value of the targeted field to return it
                $fieldSetting = Setting::where('setting_name', $validated['field_name'])->first();
                if ($fieldSetting->is_encrypted === 0) {
                    $fieldValue = $fieldSetting ? $fieldSetting->setting_value : null;
                }else {
                    $fieldValue = $fieldSetting ? $fieldSetting->getDecryptedSettingValue() : null;
                }
                session(['master_verified' => true]);
                return response()->json([
                    'success' => true,
                    'field_value' => $fieldValue,
                    'message' => 'Master password verified. Field unlocked.',
                ]);
            }
        } catch (\Exception $e) {
            return response()->json(
                [
                    'success' => false,
                    'message' => 'Wrong master password.',
                    'error' => $e->getMessage(),
                ],
                403,
            );
        }
    }
}
