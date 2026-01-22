<?php

namespace App\Http\Controllers\Front\Auth\ForgotPassword;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class IndexController extends Controller
{
    public function __invoke(Request $request)
    {

                return view('front.auth.forgot_password.index');

    }
}
