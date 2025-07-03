<?php

namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        return view('front.auth.login.index');
    }
}
