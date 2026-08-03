<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill;

use App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill\Concerns\HandlesGoodwillFailures;
use App\Http\Controllers\Controller;
use App\Models\Goodwill\OrderGoodwillAdjustment;
use App\Models\Orders\Order;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillException;
use Illuminate\Http\Request;

/**
 * Admin: withdraw a Goodwill concession.
 *
 * A separately granted permission — `goodwill.reverse`, checked directly
 * against Spatie inside the service, never through the Gate. Authority to grant
 * a concession does not imply authority to take one back.
 *
 * A written reason is REQUIRED. Reversing re-opens a balance on an order the
 * customer was told was settled, and an unexplained reversal is exactly the
 * event an audit trail exists to account for.
 *
 * Only the linked Goodwill discount is reversed. Store Credit and every other
 * active adjustment on the order survive untouched, and no payment row is
 * altered.
 */
class ReverseController extends Controller
{
    use HandlesGoodwillFailures;

    public function __invoke(Request $request, string $uniqueId, int $adjustmentId, GoodwillAdjustmentService $goodwill)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        // Scoped to the order in the path: an adjustment id alone must never be
        // enough to act on a different order's record.
        $adjustment = OrderGoodwillAdjustment::where('id', $adjustmentId)
            ->where('order_id', $order->id)
            ->firstOrFail();

        try {
            $reversed = $goodwill->reverse($adjustment, auth()->user(), $validated['reason']);
        } catch (GoodwillException $e) {
            return $this->refusal($e);
        }

        $order->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Goodwill adjustment reversed. The order balance has re-opened.',
            'adjustment_id' => $reversed->id,
            'revised_grand_total' => (float) $order->grand_total,
            'balance_due' => (float) $order->balance_due,
            'receipt_url' => route('admin.order-management.orders.receipt-download', $order->unique_id),
        ]);
    }
}
