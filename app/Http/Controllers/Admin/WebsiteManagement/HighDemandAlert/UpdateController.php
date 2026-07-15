<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\HighDemandAlert;

use App\Helpers\MediaHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\HighDemandAlert\UpdateRequest;
use App\Models\Configurations\Setting;
use App\Services\Website\HighDemandAlertService;

class UpdateController extends Controller
{
    public function __invoke(UpdateRequest $request)
    {
        $validated = collect($request->validated())
            ->except(['high_demand_alert_image', 'high_demand_alert_image_media_id', 'remove_image'])
            ->all();

        /**
         * -----------------------------
         * Alert image: upload / library pick / restore default
         * -----------------------------
         */
        if ($request->hasFile('high_demand_alert_image')) {
            $setting = Setting::where('setting_name', 'high_demand_alert_image')
                ->where('setting_type', HighDemandAlertService::SETTING_TYPE)
                ->first();

            $mediaData = MediaHelper::uploadStorageFile(
                'Public Asset',
                $request->file('high_demand_alert_image'),
                'high_demand_alert',
                $setting
            );

            if (!empty($mediaData['mediaObj'])) {
                $validated['high_demand_alert_image'] = $mediaData['mediaObj']->id;
            }
        } elseif ($request->filled('high_demand_alert_image_media_id')) {
            $validated['high_demand_alert_image'] = $request->input('high_demand_alert_image_media_id');
        } elseif ($request->boolean('remove_image')) {
            // Restore the built-in default popup image
            $validated['high_demand_alert_image'] = null;
        }

        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => HighDemandAlertService::SETTING_TYPE,
                ],
                [
                    'setting_value' => $value,
                ]
            );
        }

        HighDemandAlertService::clearCache();

        flash()->success(__('High Demand Alert updated successfully.'));

        return redirect()->route('admin.website-management.high-demand-alert.index');
    }
}
