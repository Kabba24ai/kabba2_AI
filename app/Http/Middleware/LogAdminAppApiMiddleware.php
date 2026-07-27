<?php

namespace App\Http\Middleware;

use App\Models\Global\ApiLog;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Persists a request/response pair into api_logs (service_name 'admin_app')
 * whenever the request matches an entry in config('admin_app_logging.endpoints')
 * — the ONE file that opts an endpoint into logging. Every other request
 * passes straight through untouched.
 */
class LogAdminAppApiMiddleware
{
    /**
     * Hard cap on any single stored field, regardless of column type — this
     * middleware must never be the reason a request fails or a giant nested
     * resource (equipment/product/order trees) balloons the table.
     */
    private const MAX_FIELD_LENGTH = 20000;

    public function handle(Request $request, Closure $next): Response
    {
        $name = $this->matchedName($request);

        if ($name === null) {
            return $next($request);
        }

        $requestedAt = now();
        $start = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $e) {
            $this->safeLog($request, $name, $requestedAt, $start, [
                'response_code' => 500,
                'response_json' => null,
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $statusCode = $response->getStatusCode();
        $successful = $statusCode >= 200 && $statusCode < 300;

        $this->safeLog($request, $name, $requestedAt, $start, [
            'response_code' => $statusCode,
            'response_json' => json_decode($response->getContent(), true),
            'status' => $successful ? 'success' : 'failed',
            'error_message' => $successful ? null : $response->getContent(),
        ]);

        return $response;
    }

    /**
     * Writes the log row without ever letting a logging failure (a DB error,
     * a truncation edge case, whatever) affect the actual request/response —
     * this middleware is strictly best-effort observability. Mirrors every
     * entry to disk (config('logging.channels.admin_app_api'), daily files,
     * 30-day retention) independently of the DB write, so a DB outage still
     * leaves a file trail and vice versa.
     */
    private function safeLog(Request $request, string $name, $requestedAt, float $start, array $outcome): void
    {
        $durationMs = (int) round((microtime(true) - $start) * 1000);
        [$orderId, $orderProductId] = $this->resolveOrderContext($request);
        $requestParams = $this->truncateForStorage(
            $this->cleanRequestParams($request->except(['password', 'password_confirmation']))
        );
        $responseJson = $this->truncateForStorage($outcome['response_json']);
        $errorMessage = $this->truncateString($outcome['error_message']);

        try {
            ApiLog::create([
                'service_name' => 'admin_app',
                'name' => $name,
                'order_id' => $orderId,
                'order_product_id' => $orderProductId,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'request_params' => $requestParams,
                'response_code' => $outcome['response_code'],
                'response_json' => $responseJson,
                'status' => $outcome['status'],
                'error_message' => $errorMessage,
                'requested_at' => $requestedAt,
                'responded_at' => now(),
                'duration_ms' => $durationMs,
            ]);
        } catch (Throwable $e) {
            Log::error('LogAdminAppApiMiddleware failed to write api_logs row', [
                'endpoint' => $request->path(),
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $level = $outcome['status'] === 'success' ? 'info' : 'error';

            Log::channel('admin_app_api')->{$level}($name, [
                'requested_at' => $requestedAt->toDateTimeString(),
                'responded_at' => now()->toDateTimeString(),
                'duration_ms' => $durationMs,
                'method' => $request->method(),
                'endpoint' => $request->path(),
                'order_id' => $orderId,
                'order_product_id' => $orderProductId,
                'response_code' => $outcome['response_code'],
                'status' => $outcome['status'],
                'request_params' => $requestParams,
                'response_json' => $responseJson,
                'error_message' => $errorMessage,
            ]);
        } catch (Throwable $e) {
            // Best-effort: the DB row above is the source of truth.
        }
    }

    /**
     * Caps a value (array/object bound for a json column) to MAX_FIELD_LENGTH
     * once encoded, so no single logged payload — however deeply nested —
     * can blow past a column's storage limit or bloat the table.
     */
    private function truncateForStorage($value)
    {
        if ($value === null) {
            return null;
        }

        $encoded = json_encode($value);

        if ($encoded === false || strlen($encoded) <= self::MAX_FIELD_LENGTH) {
            return $value;
        }

        return [
            '_truncated' => true,
            '_original_size' => strlen($encoded),
            'preview' => substr($encoded, 0, self::MAX_FIELD_LENGTH),
        ];
    }

    private function truncateString(?string $value): ?string
    {
        if ($value === null || strlen($value) <= self::MAX_FIELD_LENGTH) {
            return $value;
        }

        return substr($value, 0, self::MAX_FIELD_LENGTH)
            .sprintf(' …[truncated, original %d bytes]', strlen($value));
    }

    /**
     * Some Admin App callers send the SAME payload twice — once nested under
     * a numeric "0" key (as if it were array-wrapped) and once again
     * flattened at the top level (a client-side request-building bug, seen
     * on multiple endpoints — likely something like `{...[payload], ...payload}`
     * in JS). It's harmless to processing (validated() only reads declared
     * rule keys, never "0"), but it clutters the log — drop the "0" wrapper
     * here whenever it's confirmed to be an exact duplicate of the rest of
     * the payload, never touching a "0" that carries genuinely different data.
     */
    private function cleanRequestParams(array $params): array
    {
        if (! isset($params['0']) || ! is_array($params['0'])) {
            return $params;
        }

        $duplicate = $params['0'];
        $flatWithoutZero = collect($params)->except('0')->all();

        if ($duplicate == $flatWithoutZero) {
            unset($params['0']);
        }

        return $params;
    }

    private function matchedName(Request $request): ?string
    {
        foreach (config('admin_app_logging.endpoints', []) as $pattern => $name) {
            if ($request->is(ltrim($pattern, '/'))) {
                return $name;
            }
        }

        return null;
    }

    /**
     * Best-effort order context: an order_product_unique_id or
     * order_unique_id carried as a route parameter or request field,
     * resolved to the internal ids api_logs stores.
     */
    private function resolveOrderContext(Request $request): array
    {
        $orderProductUniqueId = $request->route('orderProductUniqueId')
            ?? $request->route('order_product_unique_id')
            ?? $request->input('order_product_unique_id');

        if ($orderProductUniqueId) {
            $orderProduct = OrderProduct::where('unique_id', $orderProductUniqueId)->first(['id', 'order_id']);

            if ($orderProduct) {
                return [$orderProduct->order_id, $orderProduct->id];
            }
        }

        $orderUniqueId = $request->route('orderUniqueId')
            ?? $request->route('order_unique_id')
            ?? $request->input('order_unique_id');

        if ($orderUniqueId) {
            $orderId = Order::where('unique_id', $orderUniqueId)->value('id');

            if ($orderId) {
                return [$orderId, null];
            }
        }

        return [null, null];
    }
}
