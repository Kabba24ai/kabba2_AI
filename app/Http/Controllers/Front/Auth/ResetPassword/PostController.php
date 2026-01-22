<?php

namespace App\Http\Controllers\Front\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\Rules\Password;

class PostController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => [
                'required',
                'confirmed',
                /**
                 * Enforces password validation rules:
                 * - Minimum length of 8 characters.
                 * - Must include both uppercase and lowercase letters.
                 * - Must contain at least one letter.
                 * - Must include at least one numeric digit.
                 * - Must include at least one special symbol.
                 */
                Password::min(6)
            ],
            'signature' => 'required',

        ]);

        $customer = Customer::where('email', $request->email)->first();

        if ($customer) {
            $customer->password = bcrypt($request->password);
            $customer->save();

            // Mark the token as used
            DB::table('password_reset_tokens')
                ->updateOrInsert(
                    ['email' => $request->email],
                    [
                        'token' => $request->signature,
                        'created_at' => now(),
                    ]
                );


            $signedUrl = URL::temporarySignedRoute(
                'password.reset.success', now()->addSeconds(5), ['email' => $request->email]
            );

            return redirect($signedUrl);
        }

        return back()->withErrors(['email' => 'Invalid email address.']);
    }
}
