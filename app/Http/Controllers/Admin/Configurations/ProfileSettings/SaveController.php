<?php

namespace App\Http\Controllers\Admin\Configurations\ProfileSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\ProfileSettings\SaveRequest;
use App\Models\Configurations\Setting;
use App\Helpers\MediaHelper;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        // dd($request->all());
        // die();
        $validated = $request->validated();

        /**
         * -------------------------------------
         * Handle Logo Upload using MediaHelper
         * -------------------------------------
         */
        if ($request->hasFile('logo')) {

            $setting = Setting::where('setting_name', 'logo')
                ->where('setting_type', 'Profile Settings')
                ->first();

            // Remove old logo if exists
            if ($setting && !is_null($setting->media)) {
                MediaHelper::removeFile($setting->media);
            }

            // Upload new logo
            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $request->file('logo'),
                'profile-settings',
                $setting
            );

            if (!empty($mediaData['mediaObj'])) {
                $validated['logo'] = $mediaData['mediaObj']->id; // Save media ID
            }
        }

        /**
         * -----------------------
         * Save All Settings
         * -----------------------
         */
        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Profile Settings',
                ],
                [
                    'setting_value' => $value,
                ]
            );
        }

        flash()->success(__('Profile settings updated successfully.'));
        return redirect()->back();
    }
}