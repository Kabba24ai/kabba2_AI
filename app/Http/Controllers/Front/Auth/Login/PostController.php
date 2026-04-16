<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\Front\Auth\Login\PostRequest;
use App\Models\Customers\Customer;
use App\Helpers\ConfigurationHelper;
use App\Helpers\CustomHelper;

class PostController extends Controller
{
    /**
     * Handle the incoming front customer login request.
     */
    public function __invoke(PostRequest $request)
    {

        $master_passcode = ConfigurationHelper::getDecryptedSetting('Admin Settings', 'master_passcode');

        if (Auth::guard('customer')->check()) {
            return redirect(route('front.customer.dashboard.index'))->with('success', 'You are already logged in.');
        }

        $credentials = $request->only('email', 'password');
        $passcode = $request->input('master_passcode');


        try {

           $customer = Customer::where('email', $credentials['email'])->first();

            if (!$customer) {
                return redirect()->route('front.auth.login.index')
                    ->withInput($request->only('email'))
                    ->with('error', 'No customer found with this email.');
            }

            if ($customer) {

                $account = CustomHelper::getCustomerAccountStatus($customer);

                //  If Bad Debt and still Active
                if ($account['status'] === 'Bad Debt' && $customer->status === 'Active') {

                    // Update status
                    $customer->update([
                        'status' => 'Archived' // or 'Suspended'
                    ]);

                    return redirect()->route('front.auth.login.index')
                        ->withInput($request->only('email'))
                        ->with('error', 'Your account has been suspended due to outstanding balance. Please contact support.');
                }

                // Existing inactive check
                if ($customer->status !== 'Active') {
                    return redirect()->route('front.auth.login.index')
                        ->withInput($request->only('email'))
                        ->with('error', 'Your account is inactive. Please contact support.');
                }
            }

            if (!empty($passcode) && $passcode === $master_passcode) {
                
                if ($customer) {
                    if ($customer->status !== 'Active') {
                        return redirect()->route('front.auth.login.index')
                            ->withInput($request->only('email'))
                            ->with('error', 'Your account is inactive. Please contact support.');
                    }

                    session(['master_passcode' => true]);
                    Auth::guard('customer')->login($customer);
                    $request->session()->regenerate();

                    // Reset the flag
                    if ($customer->is_reset == 1) {
                        $customer->update(['is_reset' => 0]);
                    }
                    return redirect()->route('front.checkout.index')->with('success', 'Logged in as customer using master passcode.');
                } else {
                    return redirect()->route('front.auth.login.index')
                        ->withInput($request->only('email'))
                        ->with('error' , 'No customer found with this email.');
                }
            }

            // Regular login
           
            if ($customer && $customer->status !== 'Active') {
                return redirect()->route('front.auth.login.index')
                    ->withInput($request->only('email'))
                    ->with('error', 'Your account is inactive. Please contact support.');
            }

            if (Auth::guard('customer')->attempt($credentials)) {
                $request->session()->regenerate();

                // Reset the flag
                $customer = Auth::guard('customer')->user();
                if ($customer->is_reset == 1) {
                    $customer->update(['is_reset' => 0]);
                }

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
