<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Models\Customers\Customer;

use Illuminate\Support\Facades\Log;


class PasswordResetController extends Controller
{
    /**
     * Reset customer password via AJAX.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'password' => 'required|confirmed',
        ]);

        try {
            $customer = Customer::findOrFail($validated['customer_id']);

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
            Log::info('heyyy');

            Log::info($e);


            


            return response()->json([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage(),
            ], 500);
        }
    }
}
