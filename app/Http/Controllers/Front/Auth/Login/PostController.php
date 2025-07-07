<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Front\Auth\Login\PostRequest;
use App\Models\Customers\Customer;

class PostController extends Controller
{
    /**
     * Handle the incoming front customer login request.
     */
    public function __invoke(PostRequest $request)
    {
        if (Auth::guard('customer')->check()) {
            return redirect(route('front.customer.dashboard.index'));
        }
    
        $credentials = $request->only('email', 'password');
        $passcode = $request->input('master_passcode');
    
        try {
            // If using super admin passcode
            if (!empty($passcode) && $passcode === config('app.super_admin_passcode')) {
                $customer = Customer::where('email', $credentials['email'])->first();
    
                if ($customer) {
                    Auth::guard('customer')->login($customer);
                    $request->session()->regenerate();
    
                    flash('Logged in as customer using master passcode.')->success();
                    return redirect()->route('front.customer.dashboard.index');
                } else {
                    return redirect()->route('front.auth.login.index')
                        ->withInput($request->only('email'))
                        ->withErrors(['email' => 'No customer found with this email.']);
                }
            }
    
            // Regular login
            if (Auth::guard('customer')->attempt($credentials)) {
                $request->session()->regenerate();
                return redirect()->route('front.customer.dashboard.index');
            }
    
            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'The provided credentials are incorrect.']);
    
        } catch (\Exception $e) {
            report($e);
            Auth::guard('customer')->logout();
            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Login error: ' . $e->getMessage()]);
        }
    }
    
}
