<?php

namespace App\Http\Controllers\Admin\Auth\Logout;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        if (!Auth::check()) {
            return redirect(route('admin.auth.login'));
        }

        Auth::logout();
        session()->flush();

        Session::regenerate();

        flash('Logout Successfully')->success();
        return redirect(route('admin.auth.login'));
    }
}
