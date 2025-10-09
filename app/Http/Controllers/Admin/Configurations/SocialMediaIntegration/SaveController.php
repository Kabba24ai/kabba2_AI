<?php

namespace App\Http\Controllers\Admin\Configurations\SocialMediaIntegration;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\SocialMediaIntegration\SaveRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

       foreach ($validated['social'] as $key => $value) {
            if($setting = Setting::where('setting_name', $key)->where('setting_type', 'Social Media Settings')->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Social Media settings updated successfully.'));
        return redirect()->back();
    }
}
