<?php

namespace App\Listeners;

use App\Events\Admin\Orders\PaymentConfirmedEvent;
use App\Services\ExtensionPaymentSyncService;

class SyncExtensionBillingChargeOnConfirmListener
{
    /**
     * Sync the parent BillingCharge when a child extension order's COD payment is confirmed.
     *
     * Fires on PaymentConfirmedEvent (ConfirmPaymentController).
     * Confirmation always transitions to Paid, so no status check needed.
     */
    public function handle(PaymentConfirmedEvent $event): void
    {
        ExtensionPaymentSyncService::syncParentCharge($event->order);
    }
}
