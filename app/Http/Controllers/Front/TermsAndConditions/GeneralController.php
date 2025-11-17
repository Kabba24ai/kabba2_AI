<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;

class GeneralController extends Controller
{
    public function __invoke()
    {
        // Fetch all Terms & Conditions settings in one query
        $settings = Setting::where('setting_type', 'Terms & Conditions Settings')
            ->pluck('setting_value', 'setting_name')
            ->toArray();

        return view('front.terms_and_conditions.general', [
            'settings' => $settings,
            'title' => $settings['terms_conditions_title'] ?? 'Terms & Conditions',
        ]);
    }
}
