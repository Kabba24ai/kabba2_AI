<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TermsAndConditions extends Controller
{
    public function privacy_policy(){
        return view('front.privacy-policy');
    }

    public function terms_and_conditions(){
        return view('front.terms-and-conditions');
    }

}
