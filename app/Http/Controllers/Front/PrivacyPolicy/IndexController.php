<?php

namespace App\Http\Controllers\Front\PrivacyPolicy;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return view('front.privacy_policy.index', [
            'title' => 'Privacy Policy'
        ]);
    }
}
