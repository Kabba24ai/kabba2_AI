<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use App\Models\Configurations\Setting;
use App\Models\TermsAndConditions\Terms;

class GeneralController extends Controller
{
    public function __invoke()
    {

         $settings = Terms::where('is_global', 'Yes')->orderBy('id', 'ASC')->first();

        // Remove product terms placeholder if present
        if ($settings) {
            $settings->content = str_replace('[product_terms][/product_terms]', '', $settings->content);
        }

        return view('front.terms_and_conditions.general', [
            'settings' => $settings,
            'title' =>  'Terms & Conditions',
        ]);
    }
}
