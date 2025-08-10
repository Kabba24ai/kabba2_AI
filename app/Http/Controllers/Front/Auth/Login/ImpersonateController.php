<?php
namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Helpers
use App\Helpers\SignedUrlHelper;

// Models
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;
use Illuminate\Contracts\Encryption\DecryptException;

class ImpersonateController extends Controller
{
    /**
     * Handle the impersonation login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $userId
     * @return \Illuminate\Http\RedirectResponse
     */
    public function __invoke(Request $request)
    {

        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired impersonation link.');
        }

        $requestData = $request->all();
        try {
            // decrypt everything dynamically (route + query)
            $params = SignedUrlHelper::decodeParams(
                $requestData,
                ['customer_unique_id', 'admin_unique_id', 'order_unique_id', 'order_type', 'cart_data', 'order_number']
            );
        } catch (DecryptException $e) {
            abort(403, 'Invalid or tampered parameters.');
        }


        $customerUniqueId   = $params['customer_unique_id'] ?? null;
        $adminUniqueId    = $params['admin_unique_id'] ?? null;
        $orderUniqueId    = $params['order_unique_id'] ?? null;
        $orderNumber     = $params['order_number'] ?? null;
        $orderType       = $params['order_type'] ?? null;
        $cartData       = $params['cart_data'] ?? null;


        $admin = User::find($adminUniqueId);
        if (! $admin) {
            abort(403, 'Admin not found.');
        }

        $customer = Customer::where('unique_id', $customerUniqueId)->firstOrFail();

        // Regenerate session BEFORE switching identities to prevent fixation
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Store who is impersonating (from the signed URL)
        session([
            'impersonated_by_admin' => true,
            'impersonator' => [
                'unique_id' => $adminUniqueId,
                'name' => $admin->full_name,
            ],
            'order' => [
                'unique_id' => $orderUniqueId,
                'number' => $orderNumber,
                'type' => $orderType,
                'cart_data' => $cartData
            ]
        ]);

        if (Auth::guard('customer')->check()) {
            // Already logged in as a customer, do nothing or optionally log out first
            if (Auth::guard('customer')->id() != $customer->id) {
                Auth::guard('customer')->logout();
            }
        }

        // Log in the customer on the customer guard
        Auth::guard('customer')->login($customer);

        return redirect()->route('front.home.index');
    }
}
