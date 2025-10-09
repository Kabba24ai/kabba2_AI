<?php

namespace App\Http\Controllers\Admin\Configurations\Legacy;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\UpdateSettingsRequest;

// Models
use App\Models\Configurations\Setting;

class UpdateController extends Controller
{
    public function __invoke(UpdateSettingsRequest $request)
    {
        $settings = $request->validated()['settings'] ?? [];

        foreach ($settings as $id => $value) {
            $setting = Setting::find($id);
            if ($setting) {

                // Special case for sales tax
                if ($setting->setting_name === 'sales_tax') {
                    // Convert percentage input to decimal
                    $value = floatval($value) / 100;
                }

                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Settings updated successfully.'));
        return redirect()->back();
    }
}
