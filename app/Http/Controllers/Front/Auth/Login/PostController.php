<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Front\Auth\Login\PostRequest;

class PostController extends Controller
{
    /**
     * Handle the incoming front customer login request.
     */
    public function __invoke(PostRequest $request)
    {
        // If already logged in, redirect to customer dashboard
        if (Auth::guard('customer')->check()) {
            return redirect(route('front.customer.dashboard.index'));
        }

        // Validate the credentials
        $credentials = $request->only('email', 'password');

        try {
            // Attempt login using 'customer' guard
            if (Auth::guard('customer')->attempt($credentials)) {
                $request->session()->regenerate();

                return redirect()->route('front.customer.dashboard.index');
            }

            // If login fails
            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'The provided credentials are incorrect.',
                ]);

                


        } catch (\Exception $e) {
            Auth::guard('customer')->logout();

            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Login error: ' . $e->getMessage(),
                ]);
        }
    }
}
