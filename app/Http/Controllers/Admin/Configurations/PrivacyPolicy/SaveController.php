<?php

namespace App\Http\Controllers\Admin\Configurations\PrivacyPolicy;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\PrivacyPolicy\SaveRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated['privacy'] as $key => $value) {
            Setting::where('setting_name', $key)
                ->where('setting_type', 'Privacy Policy Settings')
                ->update(['setting_value' => $value]);
        }

        flash()->success(__('Privacy Policy updated successfully.'));
        return redirect()->back();
    }
}
