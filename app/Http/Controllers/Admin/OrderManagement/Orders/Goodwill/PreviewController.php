<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill;

use App\Http\Controllers\Admin\OrderManagement\Orders\Goodwill\Concerns\HandlesGoodwillFailures;
use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\Goodwill\GoodwillAdjustmentService;
use App\Services\Goodwill\GoodwillException;

/**
 * Admin: what WOULD a Goodwill concession do to this order?
 *
 * Advisory and read-only. Nothing is locked, nothing is written, and the
 * figures returned are not binding — a payment landing a second later changes
 * them, which is exactly why the two `expected_*` values are echoed back on
 * apply and re-derived under the row lock there.
 *
 * Every figure comes from the shared pre-tax engine through
 * `GoodwillAdjustmentService`. This controller computes nothing.
 */
class PreviewController extends Controller
{
    use HandlesGoodwillFailures;

    public function __invoke(string $uniqueId, GoodwillAdjustmentService $goodwill)
    {
        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        try {
            $solution = $goodwill->preview($order, auth()->user());
        } catch (GoodwillException $e) {
            return $this->refusal($e);
        }

        return response()->json([
            'success' => true,
            'breakdown' => $solution->toDisplayArray(),
            'is_exact_close' => $solution->isExactClose(),

            // Echoed straight back on apply. The operator approves a specific
            // concession against a specific collected total; submitting both is
            // what lets the writer prove they still hold.
            'expected_goodwill_amount' => $solution->concession(),
            'expected_accepted_payment_total' => $solution->acceptedPaymentTotal(),
        ]);
    }
}
