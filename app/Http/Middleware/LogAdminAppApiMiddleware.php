<?php

namespace App\Http\Middleware;

use App\Models\Global\ApiLog;
use App\Models\Orders\Order;
use App\Models\Orders\OrderProduct;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Persists a request/response pair into api_logs (service_name 'admin_app')
 * whenever the request matches an entry in config('admin_app_logging.endpoints')
 * — the ONE file that opts an endpoint into logging. Every other request
 * passes straight through untouched.
 */
class LogAdminAppApiMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $name = $this->matchedName($request);

        if ($name === null) {
            return $next($request);
        }

        $requestedAt = now();
        $start = microtime(true);

        $response = $next($request);

        $durationMs = (int) round((microtime(true) - $start) * 1000);
        [$orderId, $orderProductId] = $this->resolveOrderContext($request);

        $statusCode = $response->getStatusCode();
        $successful = $statusCode >= 200 && $statusCode < 300;

        ApiLog::create([
            'service_name' => 'admin_app',
            'name' => $name,
            'order_id' => $orderId,
            'order_product_id' => $orderProductId,
            'method' => $request->method(),
            'endpoint' => $request->path(),
            'request_params' => $this->cleanRequestParams($request->except(['password', 'password_confirmation'])),
            'response_code' => $statusCode,
            'response_json' => json_decode($response->getContent(), true),
            'status' => $successful ? 'success' : 'failed',
            'error_message' => $successful ? null : $response->getContent(),
            'requested_at' => $requestedAt,
            'responded_at' => now(),
            'duration_ms' => $durationMs,
        ]);

        return $response;
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
