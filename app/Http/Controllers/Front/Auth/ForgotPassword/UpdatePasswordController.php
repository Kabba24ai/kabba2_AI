<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use Illuminate\Support\Facades\Hash;

class UpdatePasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:6',
        ]);

        $customer = Customer::where('email', $request->email)
            ->where('password_reset_token', $request->token)
            ->where('password_reset_token_expiry', '>', now())
            ->first();

        if (!$customer) {
            return redirect()->back()->with('error', 'Invalid or expired token.');
        }

        $customer->password = Hash::make($request->password);
        $customer->password_reset_token = null;
        $customer->password_reset_token_expiry = null;
        $customer->save();

        return redirect()->route('front.auth.login.index')
            ->with('success', 'Password reset successfully.');
    }
}
