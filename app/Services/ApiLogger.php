<?php

namespace App\Services;

use App\Models\Global\ApiLog;
use Closure;
use Illuminate\Http\Client\Response;
use Throwable;

class ApiLogger
{
    /**
     * Run $callback (an outbound API call) and, if $serviceName is whitelisted
     * in config('api_logging.services'), persist a row describing the request,
     * response, and duration. Non-whitelisted services just run $callback untouched.
     *
     * @param  string  $serviceName  e.g. 'kabba_client_api'
     * @param  string  $name         human readable action, e.g. 'Save Application Code'
     * @param  string  $method       'GET', 'POST', etc.
     * @param  string  $endpoint
     * @param  array   $requestParams
     * @param  Closure $callback     returns an Illuminate\Http\Client\Response
     * @param  ?int    $orderId         optional — not every logged call is order-scoped
     * @param  ?int    $orderProductId  optional — not every logged call is order-scoped
     * @return Response
     *
     * @throws Throwable
     */
    public static function log(
        string $serviceName,
        string $name,
        string $method,
        string $endpoint,
        array $requestParams,
        Closure $callback,
        ?int $orderId = null,
        ?int $orderProductId = null,
    ): Response {
        if (!config("api_logging.services.{$serviceName}", false)) {
            return $callback();
        }

        $requestedAt = now();
        $start = microtime(true);

        try {
            $response = $callback();

            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $respondedAt = now();

            ApiLog::create([
                'service_name' => $serviceName,
                'name' => $name,
                'order_id' => $orderId,
                'order_product_id' => $orderProductId,
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
                'request_params' => $requestParams,
                'response_code' => $response->status(),
                'response_json' => $response->json(),
                'status' => $response->successful() ? 'success' : 'failed',
                'error_message' => $response->successful() ? null : $response->body(),
                'requested_at' => $requestedAt,
                'responded_at' => $respondedAt,
                'duration_ms' => $durationMs,
            ]);

            return $response;

        } catch (Throwable $e) {

            $durationMs = (int) round((microtime(true) - $start) * 1000);

            ApiLog::create([
                'service_name' => $serviceName,
                'name' => $name,
                'order_id' => $orderId,
                'order_product_id' => $orderProductId,
                'method' => strtoupper($method),
                'endpoint' => $endpoint,
                'request_params' => $requestParams,
                'response_code' => null,
                'response_json' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'requested_at' => $requestedAt,
                'responded_at' => now(),
                'duration_ms' => $durationMs,
            ]);

            throw $e;
        }
    }
}
