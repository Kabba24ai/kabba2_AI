<?php

namespace App\Helpers;

use App\Enums\Api\ApiErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiResponseHelper
{
    /**
     * Build a standardized API error response and log the business-rule violation.
     *
     * Response shape:
     *   { success: false, error_code: "...", message: "...", status: 4xx }
     *
     * @param ApiErrorCode $code        Stable machine-readable error identifier
     * @param array        $transParams Optional parameters forwarded to trans() (e.g. ['status' => 'Rented'])
     * @param array        $context     Diagnostic data logged but never sent to the client:
     *                                  equipment_id, order_id, order_product_id, current_equipment_status, etc.
     */
    public static function error(
        ApiErrorCode $code,
        array $transParams = [],
        array $context = []
    ): JsonResponse {
        $httpStatus = $code->httpStatus();
        $message    = trans($code->translationKey(), $transParams);

        Log::channel('api_errors')->warning('API business-rule violation', array_merge([
            'error_code'  => $code->value,
            'http_status' => $httpStatus,
            'employee_id' => auth('api_user')->id(),
            'endpoint'    => request()->path(),
            'timestamp'   => now()->toISOString(),
        ], $context));

        return response()->json([
            'success'    => false,
            'error_code' => $code->value,
            'message'    => $message,
            'status'     => $httpStatus,
        ], $httpStatus);
    }
}
