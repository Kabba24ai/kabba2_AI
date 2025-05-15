<?php

namespace App\Http\Controllers\Admin\Auth\ResetPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Requests
use App\Http\Requests\Admin\Auth\ResetPassword\ResetPasswordRequest;

// Event
use App\Events\Admin\Auth\ResetPasswordEvent;

// Models
use App\Models\Iam\Personnel\UserToken;
use App\Models\Cms\Other\Location;



class UpdateController extends Controller
{


    /**
     * Handle the incoming request.
     */
    public function __invoke($token, ResetPasswordRequest $request)
    {

        if (Auth::check()) {
            return redirect(route('admin.promotions.index'));
        }

        $requestArr = \WebSetting::purify($request->all(), [
            'password',
            'confirmPassword',
        ]);

        $user_token_item = UserToken::where('token_type', 'Forgot Password')->where('token', $token)->firstOrFail();
        $user_item = $user_token_item->user;
        $user_item->password = bcrypt($requestArr['password']);
        $user_item->next_password_updated_at = \Carbon\Carbon::now()->addMonth(2)->format('Y-m-d');
        $user_item->save();

        // Add Log
        $ip = \WebSetting::getIp();
        $location_item = Location::addLocation($ip);

        // Event dispatch
        ResetPasswordEvent::dispatch([
            'user_item' => $user_item,
            'location_item' => $location_item,
        ]);

        $user_token_item->delete();

        flash(__('admin.auth.success_password_updated'))->success();

        return redirect(route('admin.auth.login'));
    }
}
