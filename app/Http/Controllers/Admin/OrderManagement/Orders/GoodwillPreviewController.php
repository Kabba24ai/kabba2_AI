<?php

namespace App\Http\Controllers\Admin\OrderManagement\Orders;

use App\Http\Controllers\Controller;
use App\Models\Orders\Order;
use App\Services\Orders\GoodwillAdjustmentService;

/**
 * What WOULD a Goodwill Adjustment do to this order?
 *
 * STRICTLY READ-ONLY. Calls the same `calculate()` the writer calls, so the
 * figures the operator approves are produced by the identical code path that
 * later commits them — a preview computed a second way is a preview that can
 * disagree with reality.
 *
 * The accepted amount is read from the order's own settled payments, never
 * from the request. The response echoes it back as `accepted_cents` so the
 * confirmation can send it as `expected_accepted_cents`, letting the service
 * detect that money moved between preview and confirm.
 */
class GoodwillPreviewController extends Controller
{
    public function __invoke(string $uniqueId)
    {
        $order = Order::where('unique_id', $uniqueId)->firstOrFail();

        $acceptedCents = (int) round(((float) $order->total_paid) * 100);

        if ($acceptedCents <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Goodwill requires a payment to have been accepted first. An order with nothing collected is a write-off, which this workflow does not cover.',
            ], 422);
        }

        $calc = GoodwillAdjustmentService::calculate($order, $acceptedCents);

        if (! $calc->succeeded()) {
            return response()->json([
                'success' => false,
                'message' => $calc->failure->message(),
                'reason'  => $calc->failure->value,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'accepted_cents' => $acceptedCents,
            'preview' => [
                'accepted'            => $acceptedCents / 100,
                'goodwill'            => $calc->goodwillCents / 100,
                'original_basis'      => $calc->originalMerchandiseCents() / 100,
                'original_tax'        => $calc->originalTaxCents / 100,
                'original_special_tax' => $calc->originalSpecialTaxCents / 100,
                'original_total'      => $calc->originalGrandTotalCents / 100,
                'revised_basis'       => $calc->revisedMerchandiseCents() / 100,
                'revised_tax'         => $calc->revisedTaxCents / 100,
                'revised_special_tax' => $calc->revisedSpecialTaxCents / 100,
                'protected_fees'      => $calc->protectedCents / 100,
                'discount'            => $calc->discountCents / 100,
                'revised_total'       => $calc->revisedGrandTotalCents / 100,
            ],
        ]);
    }
}
