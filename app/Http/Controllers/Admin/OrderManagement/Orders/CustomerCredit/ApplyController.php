<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\CustomerCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\CustomerCredit\ApplyStoreRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.2 — Order Entry Integration.
 *
 * Manual application only, per mission — employees choose the amount
 * (Apply Full Balance or Apply Partial Amount, decided in the UI); this
 * controller never applies credit automatically. Delegates entirely to
 * CustomerCreditService::redeem(), which already enforces "no negative
 * balance" and "no more than available credit" — this controller adds the
 * one order-specific rule that service cannot know about: never apply more
 * than the order's own remaining balance. Follows this screen's existing
 * JSON {success, message} convention (see ReceivePaymentController), not
 * the CRM screens' flash()+redirect() convention.
 */
class ApplyController extends Controller
{
    public function __invoke(string $uniqueId, ApplyStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();
            $user = User::findOrFail($validated['responsible_person']);
            $amount = round((float) $validated['amount'], 2);

            $alreadyApplied = CustomerCreditService::appliedToOrder($order->id);
            $remainingOrderBalance = round(max(0, $order->balance_due - $alreadyApplied), 2);

            if ($amount > $remainingOrderBalance + 0.005) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount exceeds the order's remaining balance ({$remainingOrderBalance}).",
                ], 422);
            }

            CustomerCreditService::redeem(
                customerId: $order->customer_id,
                amount: $amount,
                reason: $validated['reason'] ?: "Applied to Order #{$order->order_number}",
                responsibleUserId: $user->id,
                orderId: $order->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Store credit applied.',
            ]);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            // \RuntimeException here means CustomerCreditService::redeem()
            // rejected the request because it would exceed the customer's
            // available credit balance — surfaced verbatim, not swallowed.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Order Entry credit application error for order '.$uniqueId.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while applying store credit. Please try again.',
            ], 500);
        }
    }
}
