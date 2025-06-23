<?php

namespace App\Http\Controllers\Front\TermsAndConditions;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return view('front.terms_and_conditions.index', [
            'title' => 'Terms And Conditions'
        ]);
    }
}
