<?php

namespace App\Http\Controllers\Admin\Configurations\CompanySettings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\CompanySettings\SaveRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            if ($setting = Setting::where('setting_name', $key)->where('setting_type', 'Company Settings')->first()) {
                $setting->setting_value = $value ?? '';
                $setting->save();
            }
        }

        flash()->success(__('Company Settings updated successfully.'));

        return redirect()->back();
    }
}
