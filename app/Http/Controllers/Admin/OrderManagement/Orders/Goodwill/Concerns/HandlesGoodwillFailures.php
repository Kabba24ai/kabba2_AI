<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill\Concerns;

use App\Services\Goodwill\GoodwillException;
use Illuminate\Http\JsonResponse;

/**
 * One translation from a domain refusal to an HTTP response, shared by all
 * three Goodwill endpoints.
 *
 * The status comes from {@see \App\Services\Goodwill\GoodwillFailure::httpStatus()}
 * and nowhere else. A controller deciding for itself that "invoiced" is a 422
 * but "stale" is a 409 would be re-stating policy the domain already owns, and
 * the two would eventually disagree between endpoints.
 *
 * The machine-readable `code` is what a client should branch on; `message` is
 * written for the operator and may be reworded freely.
 *
 * A GoodwillException carrying no failure comes from a model integrity guard
 * rather than a business rule — a programming error, not something an operator
 * can act on. It degrades to 422 with no code.
 */
trait HandlesGoodwillFailures
{
    protected function refusal(GoodwillException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $e->failure?->value,
            'message' => $e->getMessage(),
        ], $e->failure?->httpStatus() ?? 422);
    }
}
