<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;

class ThankYouController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
     
        return view('front.checkout.thankyou',[
            'title'=> 'Thank-You',
        ]);
    }
}
