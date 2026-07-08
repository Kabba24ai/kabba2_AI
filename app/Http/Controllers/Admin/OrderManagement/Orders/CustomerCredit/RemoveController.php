<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\CustomerCredit;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderManagement\Orders\CustomerCredit\RemoveStoreRequest;
use App\Models\Iam\Personnel\User;
use App\Models\Orders\Order;
use App\Services\CustomerCreditService;
use Illuminate\Support\Facades\Log;

/**
 * Phase 3.2 — Order Entry Integration.
 *
 * "Remove Applied Credit" never mutates or deletes the original redemption
 * row — it records an offsetting grant referencing the same order, via
 * CustomerCreditService::createFinancialCredit(), preserving the full,
 * immutable audit trail this initiative has maintained since Phase 2.1.
 */
class RemoveController extends Controller
{
    public function __invoke(string $uniqueId, RemoveStoreRequest $request)
    {
        $validated = $request->validated();

        try {
            $order = Order::where('unique_id', $uniqueId)->firstOrFail();
            $user = User::findOrFail($validated['responsible_person']);
            $amount = round((float) $validated['amount'], 2);

            $currentlyApplied = CustomerCreditService::appliedToOrder($order->id);

            if ($amount > $currentlyApplied + 0.005) {
                return response()->json([
                    'success' => false,
                    'message' => "Amount exceeds the credit currently applied to this order ({$currentlyApplied}).",
                ], 422);
            }

            CustomerCreditService::createFinancialCredit(
                customerId: $order->customer_id,
                amount: $amount,
                reason: $validated['reason'] ?: "Reversed — Applied to Order #{$order->order_number}",
                responsibleUserId: $user->id,
                orderId: $order->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Applied credit removed.',
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Throwable $e) {
            report($e);
            Log::error('Order Entry credit removal error for order '.$uniqueId.': '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while removing applied store credit. Please try again.',
            ], 500);
        }
    }
}
