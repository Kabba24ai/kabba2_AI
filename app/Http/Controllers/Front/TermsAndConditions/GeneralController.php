<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Models\TermsAndConditions\Terms;

class GeneralController extends Controller
{
    public function __invoke()
    {
        // Fetch all Terms & Conditions settings in one query
        // $settings = Setting::where('setting_type', 'Terms & Conditions Settings')
        //     ->pluck('setting_value', 'setting_name')
        //     ->toArray();

         $settings = Terms::where('is_global', 'Yes')->orderBy('id', 'ASC')->first();


        return view('front.terms_and_conditions.general', [
            'settings' => $settings,
            'title' =>  'Terms & Conditions',
        ]);
    }
}
