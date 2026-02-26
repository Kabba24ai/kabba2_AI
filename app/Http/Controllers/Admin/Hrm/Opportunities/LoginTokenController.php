<?php

namespace App\Http\Controllers\Admin\Hrm\Opportunities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginTokenController extends Controller
{
      public function __invoke(Request $request)
    {
        $user = Auth::user();

        // short-lived SSO token with ability
        $token = $user->createToken(
            'opportunities_sso',
            ['sso']
        )->plainTextToken;

        $host = $request->getHost();

        // $redirectUrl = match ($host) {
        //     'admin.kabba.local'   => 'http://localhost:5173/',
        //     'admin.kabba.ai'      => 'https://opportunities.kabba.ai/',
        //     'admin.rentnking.com' => 'https://opportunities.rentnking.com/',
        //     default               => 'https://opportunities.kabba.ai/',
        // };


       $redirectUrl =   config('app.domains.opportunities') ;
    //    dd($redirectUrl);

        return redirect()->away(
            $redirectUrl . '/admin/SsoLogin?token=' . urlencode($token)
        );
    }
}
