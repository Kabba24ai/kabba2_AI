<?php

namespace App\Services;

use App\Enums\Billing\BillingChargeType;
use App\Models\Orders\BillingCharge;
use App\Models\Orders\Order;
use Illuminate\Support\Facades\Log;

class ExtensionPaymentSyncService
{
    /**
     * Sync the parent BillingCharge to Paid when the child extension order is paid.
     *
     * Called by payment event listeners after a payment on the child extension order
     * reaches Paid status. Safe to call multiple times — skips if no charge found or
     * already paid.
     */
    public static function syncParentCharge(Order $childOrder): void
    {
        $charge = BillingCharge::where('child_order_id', $childOrder->id)
            ->where('billing_charge_type', BillingChargeType::Extension->value)
            ->first();

        if (!$charge) {
            return;
        }

        if ($charge->isPaid()) {
            Log::channel('billing_engine')->info(
                "ExtensionPaymentSync skipped (already paid) | charge_unique_id={$charge->unique_id}" .
                " | child_order_id={$childOrder->id}"
            );
            return;
        }

        BillingEngine::markPaid($charge);

        Log::channel('billing_engine')->info(
            "ExtensionPaymentSync: parent BillingCharge marked Paid | charge_unique_id={$charge->unique_id}" .
            " | child_order_id={$childOrder->id} | child_order_number={$childOrder->order_number}"
        );
    }
}
