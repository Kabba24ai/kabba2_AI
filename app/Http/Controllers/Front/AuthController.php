<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    //

    
    public function login(){

        return view('front.login', [
            'metaTitle' => 'login - Rent n King',
        ]);

    }

    public function Register(){

        return view('front.register', [
            'metaTitle' => 'Register - Rent n King',
        ]);

    }


}
