<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;
use App\Services\ReceiptService;
use Illuminate\Http\Request;

/**
 * Reverse a Goodwill Adjustment, restoring the order's original totals.
 *
 * The customer's real payments are never touched: the balance reopens as a
 * consequence of restoring `grand_total`, not by removing tender. A reversal
 * also issues its own superseding receipt (Commit 4B), so the document history
 * stays consistent rather than being contradicted.
 */
class GoodwillReverseController extends Controller
{
    public function __invoke(string $uniqueId, Request $request)
    {
        $validated = $request->validate([
            'reversal_reason' => ['required', 'string', 'min:3', 'max:1000'],
        ], [
            'reversal_reason.required' => 'Explain why this adjustment is being reversed — a reversal nobody can account for is not an audit trail.',
        ]);

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        $result = GoodwillAdjustmentService::reverse(
            order: $order,
            reversedBy: auth()->user(),
            reversalReason: $validated['reversal_reason'],
        );

        if (! $result['ok']) {
            return response()->json([
                'success' => false,
                'message' => $result['failure']->message(),
                'reason'  => $result['failure']->value,
            ], $result['failure']->value === 'permission_denied' ? 403 : 422);
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Goodwill adjustment reversed. The original balance has been restored.',
            'order' => [
                'grand_total' => (float) $order->grand_total,
                'total_paid'  => (float) $order->total_paid,
                'balance_due' => (float) $order->balance_due,
                'is_paid'     => (bool) $order->is_paid,
            ],
            'receipt_id' => ReceiptService::currentReceipt($order)?->id,
        ]);
    }
}
