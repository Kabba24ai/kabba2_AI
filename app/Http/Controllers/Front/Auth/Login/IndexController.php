<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        // If already logged in, redirect to customer dashboard
        if (Auth::guard('customer')->check()) {
            return redirect(route('front.customer.dashboard.index'));
        }

        return view('front.auth.login.index');
    }
}
