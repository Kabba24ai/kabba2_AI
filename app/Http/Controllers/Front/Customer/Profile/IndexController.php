<?php

namespace App\Http\Controllers\Front\Customer\Profile;

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
        $customer = Auth::guard('customer')->user();

        return view('front.customer.profile.index', [
            'title' => 'Profile - Customer Dashboard',
            'customer' => $customer,
        ]);
    }
}
