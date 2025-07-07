<?php

namespace App\Http\Controllers\Front\Customer\Orders;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;


class IndexController extends Controller
{
    public function __invoke(Request $request)
    {
        $customer = Auth::guard('customer')->user() ;
     
        $customer->load('orders');

        return view('front.customer.orders.index', [
            'title' => 'Home - Customer Dashboard',
            'customer' => $customer,
        ]);
    }  
}
