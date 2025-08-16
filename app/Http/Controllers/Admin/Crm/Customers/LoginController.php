<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;

use App\Models\Customers\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Helpers\SignedUrlHelper;


class LoginController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {
        try {

            $customer = Customer::where('unique_id', $unique_id)->firstOrFail();


              $signedUrl = SignedUrlHelper::make(
                    'front.auth.login.impersonate',
                    [
                        'customer_unique_id' => $customer->unique_id,
                        'admin_unique_id' => auth()->id(),
                    ],
                    1,
                );

                return redirect($signedUrl)->with('success', "Logged in as customer using Admin’s panel.");




        } catch (\Throwable $e) {
            report($e);
            Auth::guard('customer')->logout();

            return redirect()->back()
                ->with('error', 'Login error: ' . $e->getMessage());
        }
    }
}
