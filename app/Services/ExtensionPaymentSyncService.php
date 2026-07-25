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

    /**
     * Revert the parent BillingCharge to Voided when the child extension
     * order's payment is VOIDED (Payment & Accounts Consistency Initiative,
     * Stage 2 / defect D6). The paid-sync above only ever moves the charge
     * FORWARD to Paid; a void (an in-place reversal of the settled card
     * payment) previously left the parent Extension charge stranded as
     * "Paid", so the Billing Engine showed a Paid badge for money that was
     * cancelled, and $charge->isPaid() stayed true for charge-level logic.
     *
     * Called from VoidPaymentController (void has no domain event). A no-op
     * unless the voided order is an extension child whose parent charge is
     * currently paid — so it is safe to call for EVERY void (a non-extension
     * order simply has no matching charge). Idempotent.
     *
     * REFUND is deliberately NOT handled here: a refund keeps the original
     * child payment at 'Paid' and records a separate Refund row, so the
     * extension is correctly recognized gross with the refund netted
     * separately — the charge staying Paid is correct there. Only a void
     * (which flips the original payment to Voided in place) requires this.
     *
     * Presentation/accounting: reverting to Voided changes NO report number —
     * every extension revenue/tax path already excludes a charge whose child
     * order no longer holds a live 'Paid' payment (the settled-extension
     * guard). This only makes the charge's own status/badge/isPaid() honest.
     */
    public static function revertParentChargeOnVoid(Order $childOrder, ?int $userId = null): void
    {
        $charge = BillingCharge::where('child_order_id', $childOrder->id)
            ->where('billing_charge_type', BillingChargeType::Extension->value)
            ->first();

        if (!$charge || !$charge->isPaid()) {
            return;
        }

        BillingEngine::markVoided($charge, $userId);

        Log::channel('billing_engine')->info(
            "ExtensionPaymentSync: parent BillingCharge reverted to Voided | charge_unique_id={$charge->unique_id}" .
            " | child_order_id={$childOrder->id} | child_order_number={$childOrder->order_number}"
        );
    }
}
