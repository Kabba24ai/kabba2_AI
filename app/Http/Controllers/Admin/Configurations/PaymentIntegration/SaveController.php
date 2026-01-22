<?php

namespace App\Http\Controllers\Admin\Configurations\PaymentIntegration;

use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Admin\Configurations\PaymentIntegration\PaymentIntegrationRequest;

// Models
use App\Models\Configurations\Setting;

class SaveController extends Controller
{
    public function __invoke(PaymentIntegrationRequest $request)
    {
        $validated = $request->validated();

        if(session('master_verified') !== true) {
            flash()->error(__('You havent verified your master code.'));
            return redirect()->back();
        }

        foreach ($validated as $key => $value) {
            if($setting = Setting::where('setting_name', $key)->where('setting_type', 'Payment Settings')->first()) {
                $setting->setting_value = $value;
                $setting->save();
            }
        }

        flash()->success(__('Payment Integration settings updated successfully.'));
        return redirect()->back();
    }
}
