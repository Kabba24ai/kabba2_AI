<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\Branding;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\WebsiteManagement\Branding\SaveRequest;

use App\Models\Configurations\Setting;
use App\Helpers\MediaHelper;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {

      $validated = $request->validated();


        /**
         * -----------------------------
         * Handle Site Logo Upload
         * -----------------------------
         */
        if ($request->hasFile('site_logo')) {

            $setting = Setting::where('setting_name', 'site_logo')
                ->where('setting_type', 'Website Management Branding')
                ->first();

            if ($setting && !is_null($setting->media)) {
                MediaHelper::removeFile($setting->media);
            }

            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $request->file('site_logo'),
                'branding',
                $setting
            );

            if (!empty($mediaData['mediaObj'])) {
                $validated['site_logo'] = $mediaData['mediaObj']->id;
            }
        }


        /**
         * -----------------------------
         * Handle Site Favicon Upload
         * -----------------------------
         */
        if ($request->hasFile('site_favicon')) {

            $setting = Setting::where('setting_name', 'site_favicon')
                ->where('setting_type', 'Website Management Branding')
                ->first();

            if ($setting && !is_null($setting->media)) {
                MediaHelper::removeFile($setting->media);
            }

            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $request->file('site_favicon'),
                'branding',
                $setting
            );

            if (!empty($mediaData['mediaObj'])) {
                $validated['site_favicon'] = $mediaData['mediaObj']->id;
            }
        }


        /**
         * -----------------------------
         * Save Branding Settings
         * -----------------------------
         */
        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Website Management Branding',
                ],
                [
                    'setting_value' => $value,
                ]
            );
        }

        flash()->success(__('Branding settings updated successfully.'));

        return redirect()->back();
    }
}