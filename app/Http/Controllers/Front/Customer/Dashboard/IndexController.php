<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customers\Customer;


class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $customer = Auth::guard('customer')->user() ;
     
        $customer->load('addresses.state');
        return view('front.customer.dashboard.index', [
            'title' => 'Home - Customer Dashboard',
            'customer' => $customer,
        ]);
    }
}
