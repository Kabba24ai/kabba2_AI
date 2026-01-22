<?php

namespace App\Http\Controllers\Front\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        // Validate the signed URL
        if (!$request->hasValidSignature()) {
            abort(403, 'Invalid or expired reset link.');
        }

        // Check if the token has already been used
        $email = $request->query('email');
        $signature = $request->query('signature');

        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->where('token', $signature)
            ->first();

        if ($passwordReset) {
            abort(403, 'This reset link has already been used.');
        }

        return view('front.reset-password', []);
    }
}
