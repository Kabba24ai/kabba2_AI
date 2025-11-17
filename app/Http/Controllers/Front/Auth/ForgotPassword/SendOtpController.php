<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Events\Front\Auth\SendOtpEvent;
use Illuminate\Support\Facades\Log;

class SendOtpController extends Controller
{
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        $customer = Customer::where('email', $request->email)->first();

        if (!$customer) {
            return response()->json([
                'success' => false,
                'message' => 'Email not found.'
            ]);
        }

        // ======================
        // 60-second cooldown
        // ======================
        if ($customer->last_otp_sent_at && $customer->last_otp_sent_at->diffInSeconds(now()) < 60) {

            $remaining = 60 - $customer->last_otp_sent_at->diffInSeconds(now());

            return response()->json([
                'success' => false,
                'cooldown' => true,
                'remaining' => $remaining,
                'message' => "Please wait {$remaining} seconds before resending OTP."
            ]);
        }

        // Generate OTP
        $otp = rand(1000, 9999);

        try {
            // Store OTP in the database
            $customer->update([
                'current_otp'        => $otp,
                'last_otp_sent_at'   => now(),
            ]);

            // Fire email event
            event(new SendOtpEvent($request->email, $otp));

            // Logging for debugging
            Log::info("OTP sent to email: {$request->email}, OTP: {$otp}");

            return response()->json([
                'success' => true,
                'message' => 'OTP sent successfully!',
                'otp_sent_at' => now()->toDateTimeString()
            ]);
        } catch (\Throwable $e) {

            Log::error('Failed to send OTP email.', [
                'email' => $request->email,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while sending OTP.'
            ]);
        }
    }
}
