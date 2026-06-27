<?php

namespace App\Http\Controllers\Admin\Configurations\TimezoneSettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\TimezoneSettings\SaveRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        if (session('master_verified') !== true) {
            flash()->error(__('You havent verified your master code.'));
            return redirect()->back();
        }

        foreach ($validated as $key => $value) {
            if ($setting = Setting::where('setting_name', $key)->where('setting_type', 'Time Zone Settings')->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Time Zone Settings updated successfully.'));
        return redirect()->back();
    }
}
