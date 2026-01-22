<?php

namespace App\Http\Controllers\Front\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SuccessController extends Controller
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

        return view('front.reset-password-success', []);
    }
}
