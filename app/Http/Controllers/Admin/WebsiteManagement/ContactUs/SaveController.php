<?php

namespace App\Http\Controllers\Admin\WebsiteManagement\ContactUs;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\WebsiteManagement\ContactUsSection\SaveRequest;
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveRequest $request)
    {
        $validated = $request->validated();

        foreach ($validated as $key => $value) {

            Setting::updateOrCreate(
                [
                    'setting_name' => $key,
                    'setting_type' => 'Website Management Contact Us Section',
                ],
                [
                    'setting_value' => $value,
                ]
            );

        }

        flash()->success(__('Contact Us section updated successfully.'));

        return redirect()->back();
    }
}