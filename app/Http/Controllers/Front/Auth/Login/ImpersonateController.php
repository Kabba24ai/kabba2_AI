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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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
                ['customer_unique_id', 'admin_unique_id', 'order_unique_id', 'order_type', 'cart_ref', 'order_number']
            );
        } catch (DecryptException $e) {
            abort(403, 'Invalid or tampered parameters.');
        }


        $customerUniqueId   = $params['customer_unique_id'] ?? null;
        $adminUniqueId    = $params['admin_unique_id'] ?? null;
        $orderUniqueId    = $params['order_unique_id'] ?? null;
        $orderNumber     = $params['order_number'] ?? null;
        $orderType       = $params['order_type'] ?? null;
        $cartRef        = $params['cart_ref'] ?? null;
        $cartData       = null;

        if ($cartRef) {
            $cartData = Cache::pull('reorder_cart_data:' . $cartRef, []);
        }


        $admin = User::find($adminUniqueId);
        if (! $admin) {
            abort(403, 'Admin not found.');
        }

        $customer = Customer::where('unique_id', $customerUniqueId)->firstOrFail();

        // Track who's currently logged in before impersonation
        $previousCustomerId = Auth::guard('customer')->id();

        // If a different customer is already logged in, log them out first
        if (Auth::guard('customer')->check() && $previousCustomerId !== $customer->id) {
            // Log::info('Customer logged out for impersonation', [
            //     'previous_customer_id' => $previousCustomerId,
            //     'new_customer_id' => $customer->id,
            //     'admin_id' => $adminUniqueId,
            // ]);
            Auth::guard('customer')->logout();
        }

        // Store impersonation context in session (BEFORE regenerating token)
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

        // Only regenerate CSRF token, don't invalidate entire session
        // This keeps admin logged in while allowing customer impersonation
        $request->session()->regenerateToken();

        // Log in the customer on the customer guard (separate from admin guard)
        Auth::guard('customer')->login($customer);

        // Audit log for impersonation
        // Log::info('Admin impersonated customer', [
        //     'admin_id' => $adminUniqueId,
        //     'admin_name' => $admin->full_name,
        //     'customer_id' => $customer->id,
        //     'customer_email' => $customer->email,
        //     'order_unique_id' => $orderUniqueId,
        //     'ip_address' => $request->ip(),
        //     'user_agent' => $request->userAgent(),
        // ]);


        // Reset the flag if needed
        if ($customer->is_reset == 1) {
            $customer->update(['is_reset' => 0]);
        }


        return redirect()->route('front.home.index');
    }
}
