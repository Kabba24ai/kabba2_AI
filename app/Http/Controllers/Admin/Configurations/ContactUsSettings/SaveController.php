<?php

namespace App\Http\Controllers\Admin\Configurations\ContactUsSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\ContactUsSettings\SaveRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {
            Setting::where('setting_name', $key)
                ->where('setting_type', 'Contact Us Settings')
                ->update(['setting_value' => $value]);
        }

        flash()->success(__('Contact Us Settings updated successfully.'));
        return redirect()->back();
    }
}
