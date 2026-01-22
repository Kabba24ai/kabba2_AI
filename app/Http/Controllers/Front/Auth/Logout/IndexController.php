<?php

namespace App\Http\Controllers\Front\Auth\Logout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

      

        if (!Auth::guard('customer')->check()) {
            return redirect(route('front.auth.login.index'));
        }

        // Check if the session was started via impersonation
        if (session()->has('impersonated_by_admin')) {
            session()->forget([
                'impersonated_by_admin',
                'impersonator',
                'order',
            ]);
        }

        if (session()->has('master_passcode')) {
            session()->forget('master_passcode');
        }

        Auth::guard('customer')->logout();

   

        Session::flush();
        Session::regenerate();
   
        return redirect(route('front.auth.login.index'))->with('success', 'Logout Successfully');
    }
}
