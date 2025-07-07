<?php

namespace App\Http\Controllers\Front\Customer\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Customers\Customer;
use App\Helpers\CustomHelper;

class IndexController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $customer = Auth::guard('customer')->user();

        
    // Format DOB for JS datepicker using your helper
    $formattedDob = CustomHelper::formatDate($customer->dob, config('app.date.date_format'));


        return view('front.customer.profile.index', [
            'title' => 'Profile - Customer Dashboard',
            'customer' => $customer,
            'formattedDob' => $formattedDob ?? null ,
        ]);
    }
}
