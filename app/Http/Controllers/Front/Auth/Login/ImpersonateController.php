<?php
namespace App\Http\Controllers\Front\Auth\Login;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Customers\Customer;
use App\Models\Iam\Personnel\User;

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

        $uniqueId   = $request->query('unique_id');
        $adminId    = (int) $request->query('admin_id');

        // Basic sanity checks (optional but good hygiene)
        if ($adminId <= 0) {
            abort(403, 'Invalid admin.');
        }

        // Confirm admin exists (prevents orphaned sessions)
        // If your admin model is different, change App\Models\User accordingly.
        $admin = User::find($adminId);
        if (! $admin) {
            abort(403, 'Admin not found.');
        }

        $customer = Customer::where('unique_id', $uniqueId)->firstOrFail();

        // Regenerate session BEFORE switching identities to prevent fixation
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Store who is impersonating (from the signed URL)
        session([
            'impersonated_by_admin' => true,
            'impersonator_id' => $adminId,
            'impersonator_name' => $admin->full_name,
        ]);

        // Log in the customer on the customer guard
        Auth::guard('customer')->login($customer);

        return redirect()->route('front.home.index');
    }
}
