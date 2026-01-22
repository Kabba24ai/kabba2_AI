<?php

namespace App\Http\Middleware\Front;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerIsActive
{
    public function handle(Request $request, Closure $next)
    {
        $customer = Auth::guard('customer')->user();

        if ($customer && $customer->status !== 'Active') {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('front.auth.login.index')
                ->with('error', 'Your account has been suspended. Please contact support.');
        }

        if ($customer && $customer->is_reset == 1) {
            Auth::guard('customer')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('front.auth.login.index')
                ->with('error', 'Your password has been reset. Please update it before logging in.');
        }


        return $next($request);
    }
}
