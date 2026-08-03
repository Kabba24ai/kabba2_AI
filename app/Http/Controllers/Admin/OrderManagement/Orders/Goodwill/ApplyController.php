<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill;

use App\Enums\Goodwill\GoodwillReason;
use App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill\Concerns\HandlesGoodwillFailures;
use App\Http\Controllers\Controller;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillApplyRequest;
use App\Services\Goodwill\GoodwillException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

/**
 * Admin: apply a manager-authorised Goodwill concession so the order's revised
 * total equals what the customer actually paid.
 *
 * ── WHAT THIS CONTROLLER DOES NOT DO ──────────────────────────────────────
 *
 * It does not size the concession, read a payment total, judge eligibility, or
 * touch an order. It validates the SHAPE of the request, resolves two models,
 * and hands the decision to `GoodwillAdjustmentService`.
 *
 * In particular the note-required rule for reason OTHER is deliberately NOT
 * validated here. It is a business rule the service already owns and refuses
 * with a typed failure; restating it as a validation rule would give the same
 * condition two different error shapes depending on which layer noticed first.
 *
 * ── THE TWO EXPECTED VALUES ───────────────────────────────────────────────
 *
 * The operator approves a specific concession against a specific collected
 * total. Both are submitted back and re-derived under the row lock. If either
 * has moved, the write is refused rather than silently applied against figures
 * nobody approved.
 *
 * Goodwill creates no payment row. It moves the order down to meet the money.
 */
class ApplyController extends Controller
{
    use HandlesGoodwillFailures;

    public function __invoke(Request $request, string $uniqueId, GoodwillAdjustmentService $goodwill)
    {
        $validated = $request->validate([
            'reason_code' => ['required', new Enum(GoodwillReason::class)],
            'note' => ['nullable', 'string', 'max:1000'],
            'approved_by' => ['required', 'integer', 'exists:users,id'],
            'expected_goodwill_amount' => ['required', 'numeric', 'min:0.01'],
            'expected_accepted_payment_total' => ['required', 'numeric', 'min:0.01'],
            'idempotency_token' => ['required', 'string', 'max:64'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();
        $approver = User::find($validated['approved_by']);

        try {
            $adjustment = $goodwill->apply(new GoodwillApplyRequest(
                order: $order,
                reason: GoodwillReason::from($validated['reason_code']),
                note: $validated['note'] ?? null,
                idempotencyKey: 'order_goodwill:'.$validated['idempotency_token'],
                operator: auth()->user(),
                approver: $approver,
                expectedGoodwillAmount: (float) $validated['expected_goodwill_amount'],
                expectedAcceptedPaymentTotal: (float) $validated['expected_accepted_payment_total'],
                sourceInterface: 'admin_order_pending_payment',
            ));
        } catch (GoodwillException $e) {
            return $this->refusal($e);
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Goodwill adjustment applied. The order is now settled by the payment already collected.',
            'adjustment_id' => $adjustment->id,
            'goodwill_amount' => (float) $adjustment->concessionAmount(),
            'rounding_residual' => (float) $adjustment->rounding_residual,
            'revised_grand_total' => (float) $order->grand_total,
            'balance_due' => (float) $order->balance_due,

            // The receipt was refreshed after commit; this is where to read it.
            'receipt_url' => route('admin.order-management.orders.receipt-download', $order->unique_id),
        ]);
    }
}
