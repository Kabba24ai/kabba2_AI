<?php

namespace App\Http\Controllers\Admin\GiftCards\Concerns;

use App\Services\GiftCards\GiftCardException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

/**
 * One translation from a gift card refusal to an HTTP response, shared by
 * every gift card endpoint.
 *
 * The status comes from {@see \App\Services\GiftCards\GiftCardFailure::httpStatus()}
 * and nowhere else — see that method for why. `code` is what a client should
 * branch on; `message` is written for the operator and may be reworded.
 *
 * An exception carrying no failure comes from a model integrity guard rather
 * than a business rule — a programming error, not something an operator can
 * act on — and degrades to 422 with no code.
 */
trait HandlesGiftCardFailures
{
    protected function refusalJson(GiftCardException $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'code' => $e->failure?->value,
            'message' => $e->getMessage(),
        ], $e->failure?->httpStatus() ?? 422);
    }

    /**
     * The same refusal for a form post. The operator's input is preserved so
     * a rejected $600 grant does not make them retype the recipient, message
     * and reason to try $500.
     */
    protected function refusalBack(GiftCardException $e): RedirectResponse
    {
        return back()->withInput()->with('error', $e->getMessage());
    }
}
