<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //

    public function customer_account(){


        return view('front.customer_account',[
            'metaTitle' => ' Customer Account - Rent n King'
        ]);

    }

}
