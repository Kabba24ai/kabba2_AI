<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Customers\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;


class LoginController extends Controller
{
    public function __invoke(Request $request, $unique_id)
    {
        try {

            $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

            Auth::guard('customer')->login($customer);
            //$request->session()->regenerate();

            return redirect()->route('front.customer.dashboard.index')
            ->with('success', "Logged in as customer using Admin’s panel.");

        } catch (\Throwable $e) {
            report($e);
            Auth::guard('customer')->logout();

            return redirect()->back()
                ->with('error', 'Login error: ' . $e->getMessage());
        }
    }
}
