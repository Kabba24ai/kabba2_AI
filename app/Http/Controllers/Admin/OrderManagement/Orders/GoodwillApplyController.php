<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\GoodwillApplyRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\ReceiptService;

/**
 * Apply a Goodwill Adjustment to an order that has already been paid in part.
 *
 * PAYMENT ORDERING. This endpoint deliberately does NOT take a payment. The
 * real tender is recorded first, through the existing Receive Payment flow,
 * which is left completely untouched; Goodwill is then applied against the
 * money that has actually settled.
 *
 * The alternative — bundling both into one submit — would put a live gateway
 * charge inside a transaction that a Goodwill refusal could roll back. For a
 * card that is unacceptable: the charge is irreversible from our side, so
 * rolling back the payment ROW would destroy the record of money that really
 * moved, which is far worse than an order left partially paid. Splitting them
 * means each operation is individually atomic and a Goodwill refusal cannot
 * cost the customer their payment record.
 *
 * It also matches the arithmetic. Goodwill reduces the basis so the revised
 * total equals what was accepted, so the accepted figure must already include
 * the payment just taken. Applying Goodwill first would close the order at the
 * pre-payment total and turn the incoming tender into an overpayment.
 *
 * The controller adds no financial logic of its own. It resolves identities,
 * delegates, and translates the service's typed refusal into an HTTP status.
 * Every guard — permission, one-active, stale state, AR, invoice — lives in
 * the service, inside its transaction, under the order row lock.
 */
class GoodwillApplyController extends Controller
{
    public function __invoke(string $uniqueId, GoodwillApplyRequest $request)
    {
        $validated = $request->validated();

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        $approvedBy = User::findOrFail($validated['approved_by']);
        $performedBy = auth()->user() ?? $approvedBy;

        // The most recent settled payment, linked for audit. Goodwill is NOT
        // that payment and never creates one — this only records which real
        // tender the concession was granted alongside.
        $linkedPaymentId = $order->payments()->settled()->latest('id')->value('id');

        $result = GoodwillAdjustmentService::apply(
            order: $order,
            expectedAcceptedCents: (int) $validated['expected_accepted_cents'],
            reason: GoodwillReasonCode::from($validated['reason_code']),
            reasonNote: $validated['reason_note'] ?? null,
            approvedBy: $approvedBy,
            performedBy: $performedBy,
            idempotencyToken: $validated['idempotency_token'],
            orderPaymentId: $linkedPaymentId,
        );

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['failure']->message(),
                'reason'  => $result['failure']->value,
            ], self::statusFor($result['failure']->value));
        }

        $order->refresh();

        return response()->json([
            'success'  => true,
            'replayed' => $result['replayed'],
            'message'  => $result['replayed']
                ? 'This Goodwill adjustment was already applied.'
                : 'Goodwill adjustment applied. The order is now paid in full.',
            'order' => [
                'grand_total' => (float) $order->grand_total,
                'total_paid'  => (float) $order->total_paid,
                'balance_due' => (float) $order->balance_due,
                'is_paid'     => (bool) $order->is_paid,
            ],
            // The adjusted document, so the page can offer it immediately
            // rather than leaving the operator to hunt for it.
            'receipt_id' => ReceiptService::currentReceipt($order)?->id,
        ]);
    }

    /**
     * A refusal is not a server error and must not read like one.
     *
     * 409 for "the world changed under you" — the operator's remedy is to
     * reload and look again. 422 for "this order cannot take a Goodwill
     * adjustment" — reloading will not help. 403 for authority.
     */
    private static function statusFor(string $reason): int
    {
        return match ($reason) {
            'permission_denied' => 403,
            'stale_order_state', 'active_adjustment_exists' => 409,
            default => 422,
        };
    }
}
