<?php

namespace App\Http\Controllers\Admin\Configurations\CommunicationSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\CommunicationSettings\SaveRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        if(session('master_verified') !== true) {
            flash()->error(__('You havent verified your master code.'));
            return redirect()->back();
        }

        foreach ($validated as $key => $value) {
            if($setting = Setting::where('setting_name', $key)->where('setting_type', 'Communication Settings')->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Communication Settings updated successfully.'));
        return redirect()->back();
    }
}
