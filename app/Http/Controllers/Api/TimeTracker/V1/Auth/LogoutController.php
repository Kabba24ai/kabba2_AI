<?php

namespace App\Http\Controllers\Api\TimeTracker\V1\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Api\BaseController;

class LogoutController extends BaseController
{


    /**
     * Logout
     * @group Admin App
     * @authenticated
     */
    public function __invoke(Request $request)
    {

        // Revoke the current user's token
        $request->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => trans("messages.api.admin.v1.auth.logout"),
        ]);
    }
}
