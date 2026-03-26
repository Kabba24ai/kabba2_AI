<?php

namespace App\Http\Controllers\Admin\Crm\Customers;

use App\Http\Controllers\Controller;
use App\Models\Customers\Customer;
use App\Helpers\MediaHelper;
use Illuminate\Http\JsonResponse;

class LicenseDeleteController extends Controller
{
    public function __invoke(string $unique_id, string $type): JsonResponse
    {
        $customer = Customer::where('unique_id', $unique_id)->firstOrFail();

        try {

            // FRONT DELETE
            if ($type === 'front' && $customer->licenseFront) {

                MediaHelper::removeFile($customer->licenseFront);

                $customer->update([
                    'license_front_media_id' => null
                ]);
            }

            // BACK DELETE
            if ($type === 'back' && $customer->licenseBack) {

                MediaHelper::removeFile($customer->licenseBack);

                $customer->update([
                    'license_back_media_id' => null
                ]);
            }

            //  AUTO CLEAR EXPIRY IF BOTH EMPTY
            if (!$customer->license_front_media_id && !$customer->license_back_media_id) {
                $customer->update([
                    'license_expiry_date' => null
                ]);
            }

            return response()->json([
                'success' => true
            ]);

        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete license'
            ]);
        }
    }
}