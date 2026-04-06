<?php

namespace App\Http\Controllers\Admin\Hrm\Opportunities;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class LoginTokenController extends Controller
{
      public function __invoke(Request $request)
    {
        try {
            $user = Auth::user();

             if (!$user) {

                // Log::warning('SSO Login attempt without authenticated user', [
                //     'ip' => $request->ip(),
                //     'url' => $request->fullUrl(),
                // ]);

                abort(401, 'Unauthorized');
            }


            // short-lived SSO token with ability
            $token = $user->createToken(
                'opportunities_sso',
                ['sso']
            )->plainTextToken;

            // $host = $request->getHost();

            $redirectUrl =   config('app.domains.opportunities') ;

            // Log::info('SSO token generated successfully', [
            //     'user_id' => $user->id,
            //     'email' => $user->email ?? null,
            //     'redirect_url' => $redirectUrl,
            //     'ip' => $request->ip(),
            // ]);

    
            return redirect()->away(
                $redirectUrl . '/admin/SsoLogin?token=' . urlencode($token)
            );
        } catch (\Exception $e) {
            // Log::error('SSO LoginTokenController error', [
            //     'message' => $e->getMessage(),
            //     'user_id' => optional(Auth::user())->id,
            //     'trace' => $e->getTraceAsString(),
            // ]);

            abort(500, 'Something went wrong');
        }
    }
}
