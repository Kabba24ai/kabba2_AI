<?php

namespace App\Listeners;

use App\Enums\Orders\OrderPaymentStatus;
use App\Events\Admin\Orders\PaymentInitiateEvent;
use App\Services\ExtensionPaymentSyncService;

class SyncExtensionBillingChargeOnPaymentListener
{
    /**
     * Sync the parent BillingCharge when a child extension order reaches Paid status.
     *
     * Fires on PaymentInitiateEvent (ReceivePaymentController / ChargeCreditCardController).
     * Only triggers when the payment status is fully Paid — skips partial payments,
     * pending COD rows, and failed transactions.
     */
    public function handle(PaymentInitiateEvent $event): void
    {
        $payment = $event->payment;

        $status = $payment?->status;
        $isPaid = $status instanceof OrderPaymentStatus
            ? $status->isPaid()
            : ($status === OrderPaymentStatus::Paid->value);

        if (!$isPaid) {
            return;
        }

        ExtensionPaymentSyncService::syncParentCharge($event->order);
    }
}
