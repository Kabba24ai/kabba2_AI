<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;

class ResetPasswordFormController extends Controller
{
    public function __invoke(Request $request, string $token)
    {
        $customer = Customer::where('password_reset_token', $token)
            ->where('password_reset_token_expiry', '>', now())
            ->first();

        if (!$customer) {
            return redirect()->route('front.auth.forgot-password.index')
                ->with('error', 'Invalid or expired reset token.');
        }

        return view('front.auth.forgot_password.reset', [
            'token' => $token,
            'email' => $customer->email,
        ]);
    }
}
