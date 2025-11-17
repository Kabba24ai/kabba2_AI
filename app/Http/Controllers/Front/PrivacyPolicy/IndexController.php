<?php

namespace App\Http\Controllers\Front\PrivacyPolicy;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;

class IndexController extends Controller
{
    public function __invoke()
    {
        // Fetch all Privacy Policy settings in one query
        $settings = Setting::where('setting_type', 'Privacy Policy Settings')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

            // dd($settings);

        return view('front.privacy_policy.index', [
            'settings' => $settings,
            'title'    => $settings['privacy_policy_title'] ?? 'Privacy Policy',
        ]);
    }
}
