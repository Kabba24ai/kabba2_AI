<?php

namespace App\Http\Controllers\Admin\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Models
use App\Models\Iam\Personnel\UserToken;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke($token, Request $request)
    {

        if (!$request->hasValidSignature()) {
            return redirect(route('admin.auth.login'));
        }

        if (Auth::check()) {
            return redirect(route('admin.promotions.index'));
        }

        $user_item = UserToken::where('token_type','Forgot Password')->where('token', $token)->firstOrFail();

        return view('admin.auth.reset_password.index', [
            'user_item' => $user_item,
            'current_step'=>1,
        ]);
    }
}
