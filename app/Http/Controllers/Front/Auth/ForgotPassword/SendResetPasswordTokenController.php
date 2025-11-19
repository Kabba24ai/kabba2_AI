<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use Illuminate\Support\Str;
use App\Events\Front\Auth\SendResetPasswordEvent;
use Illuminate\Support\Facades\Log;

class SendResetPasswordTokenController extends Controller
{
    public function __invoke(Request $request)
    {
        $email = strtolower(trim($request->get('email')));
        if (!$email) {
            return response()->json(['success' => false, 'message' => 'Email is required.']);
        }

        $customer = Customer::whereRaw('LOWER(email) = ?', [$email])->first();

        if (!$customer) {
            return redirect()
                ->back()
                ->with('error', 'Customer with this email does not exist.');
        }

        // Generate token & expiry
        $token = Str::random(64);
        $customer->password_reset_token = $token;
        $customer->password_reset_token_expiry = now()->addMinutes(10); // token valid for 10 minutes
        $customer->save();



        // Fire event to send reset password email
        event(new SendResetPasswordEvent($customer->email, $token));

        return redirect()
            ->back()
            ->with('success', 'Reset password link sent successfully to your email.');
    }
}
