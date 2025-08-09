<?php

namespace App\Http\Controllers\Admin\Crm\Customers\Login;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Str;

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

        // Build a temporary signed URL carrying customer unique_id + admin id + admin guard
        $signedUrl = URL::temporarySignedRoute(
            'front.auth.login.impersonate',
            now()->addMinutes(5),
            [
                'unique_id'  => $customer->unique_id,
                'admin_id'   => auth()->id(),
            ]
        );

        return redirect($signedUrl);
    }
}
