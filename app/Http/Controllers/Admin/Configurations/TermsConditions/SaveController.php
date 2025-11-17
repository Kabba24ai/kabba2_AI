<?php

namespace App\Http\Controllers\Admin\Configurations\TermsConditions;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Configurations\TermsConditions\SaveRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated['terms'] as $key => $value) {
            Setting::where('setting_name', $key)
                ->where('setting_type', 'Terms & Conditions Settings')
                ->update(['setting_value' => $value]);
        }

        flash()->success(__('Terms & Conditions updated successfully.'));
        return redirect()->back();
    }
}
