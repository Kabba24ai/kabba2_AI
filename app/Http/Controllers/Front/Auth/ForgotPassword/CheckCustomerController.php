<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Customers\Customer;
use App\Events\Front\Auth\SendOtpEvent;
use Illuminate\Support\Facades\Log;

class CheckCustomerController extends Controller
{
    public function __invoke(Request $request)
    {
        $email = strtolower(trim($request->get('email')));

        if (!$email) {
            return response()->json(['valid' => false]);
        }

        $exists = Customer::whereRaw('LOWER(email) = ?', [$email])->exists();

        // For forgot password, email must exist
        return response()->json(['valid' => $exists]);
    }
}
