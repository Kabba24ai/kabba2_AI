<?php

namespace App\Http\Controllers\Admin\Configurations\MailSendSettings;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\MailSendSettings\SaveMailSendRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(SaveMailSendRequest $request)
    {
        $validated = $request->validated();

        // If mail_username or mail_password is being changed, verify master code
        if ($request->has('mail_username') || $request->has('mail_password')) {
            if(session('master_verified') !== true) {
                flash()->error(__('You havent verified your master code.'));
                return redirect()->back();
            }
        }

        foreach ($validated as $key => $value) {
            if($setting = Setting::where('setting_name', $key)->where('setting_type', 'Mail Send Settings')->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Mail Send Settings updated successfully.'));
        return redirect()->back();
    }
}
