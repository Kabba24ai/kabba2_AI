<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Front\Auth\Login\PostRequest;
use App\Models\Customers\Customer;
use App\Helpers\ConfigurationHelper;

class PostController extends Controller
{
    /**
     * Handle the incoming front customer login request.
     */
    public function __invoke(PostRequest $request)
    {

        $adminSettings = ConfigurationHelper::getSettings('Admin Settings');

        if (Auth::guard('customer')->check()) {
            return redirect(route('front.customer.dashboard.index'))->with('success', 'You are already logged in.');
        }
    
        $credentials = $request->only('email', 'password');
        $passcode = $request->input('master_passcode');
    
        try {
            
            if (!empty($passcode) && $passcode === $adminSettings['master_passcode']) {
                $customer = Customer::where('email', $credentials['email'])->first();
    
                if ($customer) {
                    if ($customer->status !== 'Active') {
                        return redirect()->route('front.auth.login.index')
                            ->withInput($request->only('email'))
                            ->with('error', 'Your account is inactive. Please contact support.');
                    }


                    Auth::guard('customer')->login($customer);
                    $request->session()->regenerate();
                    return redirect()->route('front.customer.dashboard.index')->with('success', 'Logged in as customer using master passcode.');
                } else {
                    return redirect()->route('front.auth.login.index')
                        ->withInput($request->only('email'))
                        ->with('error' , 'No customer found with this email.');
                }
            }
    
            // Regular login
            $customer = Customer::where('email', $credentials['email'])->first();
            if ($customer && $customer->status !== 'active') {
                return redirect()->route('front.auth.login.index')
                    ->withInput($request->only('email'))
                    ->with('error', 'Your account is inactive. Please contact support.');
            }
            
            if (Auth::guard('customer')->attempt($credentials)) {
                $request->session()->regenerate();
                return redirect()->route('front.customer.dashboard.index')->with('success', 'You are already logged in.');
            }
    
            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->with('error' , 'The provided credentials are incorrect.');
        } catch (\Exception $e) {
            report($e);
            Auth::guard('customer')->logout();
            return redirect()->route('front.auth.login.index')
                ->withInput($request->only('email'))
                ->with('error' , 'Login error: ' . $e->getMessage());
        }
    }   
}
