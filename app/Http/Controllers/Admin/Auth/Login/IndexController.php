<?php

namespace App\Http\Controllers\Admin\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {

        if (Auth::check()) {
            $user = auth()->user();
            return redirect(route('admin.dashboard.index'));
        }
        return view('admin.auth.login.index', []);
    }
}
