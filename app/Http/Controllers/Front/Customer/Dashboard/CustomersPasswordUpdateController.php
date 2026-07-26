<?php

namespace App\Http\Controllers\Front\Customer\Dashboard;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Log;



class CustomersPasswordUpdateController extends Controller
{
    /**
     * Reset the AUTHENTICATED customer's own password via AJAX.
     *
     * Security: never trust a request-supplied customer id. The password is
     * always applied to Auth::guard('customer')->user() — the session owner —
     * so a customer cannot set another customer's password by posting a
     * different customer_id.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|confirmed',
        ]);

        try {
            $customer = Auth::guard('customer')->user();

            $customer->update([
                'password' => Hash::make($validated['password']),
                'is_reset' => 1,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully.',
            ]);
        } catch (\Throwable $e) {
            report($e);
        
            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage(),
            ], 500);
        }
    }
}
