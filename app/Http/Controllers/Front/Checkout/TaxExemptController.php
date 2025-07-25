<?php

namespace App\Http\Controllers\Front\Checkout;

use App\Helpers\ConfigurationHelper;
use App\Http\Controllers\Controller;

// Requests
use App\Http\Requests\Front\Checkout\TaxExemptRequest;

class TaxExemptController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(TaxExemptRequest $request)
    {
        $validatedData = $request->validated();

        $adminSettings = ConfigurationHelper::getSettings('Admin Settings'); // Ensure settings are loaded
        $masterPasscode = $adminSettings['master_passcode'] ?? null;
        $isTaxExempt = $validatedData['is_tax_exempt'] ?? false;

        if ($isTaxExempt) {
            // Handle tax exempt logic
            if (
                isset($validatedData['passcode']) &&
                !empty($validatedData['passcode']) &&
                $validatedData['passcode'] === $masterPasscode
            ) {
                session(['tax_exempt' => true]);
                // Logic for handling valid tax exempt code
                return response()->json([
                    'success' => true,
                    'message' => 'Admin code verified successfully.'
                ], 200);
            }
        }else{
            // If not tax exempt, clear the session
            session()->forget('tax_exempt');
            return response()->json([
                'success' => true,
                'message' => 'Tax exemption cleared.'
            ], 200);
        }

        if(session()->has('tax_exempt')) {
            session()->forget('tax_exempt'); // Clear tax exempt session if already set
        }

        return response()->json([
            'success' => false,
            'message' => 'Admin code verification failed.'
        ], 422);
    }
}
