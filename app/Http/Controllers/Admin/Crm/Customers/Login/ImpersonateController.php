<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Login;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

// Helpers
use App\Helpers\SignedUrlHelper;

// Models
use App\Models\Customers\Customer;

class ImpersonateController extends Controller
{
    /**
     * Handle the impersonation request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request, $unique_id)
    {
        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        $signedUrl = SignedUrlHelper::make(
            'front.auth.login.impersonate',
            [
                'customer_unique_id' => $customer->unique_id,
                'admin_unique_id' => auth()->id(),
            ],
            1,
        );

        return redirect($signedUrl);
    }
}
