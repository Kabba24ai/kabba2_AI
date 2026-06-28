<?php

namespace App\Listeners;

use App\Events\Admin\Orders\PaymentInitiateEvent;
use App\Services\FunnelLifecycleService;

class StopCodFunnelsOnPaymentListener
{
    public function handle(PaymentInitiateEvent $event): void
    {
        $order   = $event->order;
        $payment = $event->payment;

        // Only trigger when a payment reaches Paid status
        if (!$payment || !in_array($payment->status ?? null, ['Paid', 'PartialPayment'])) {
            return;
        }

        // Only trigger if the order had a COD/Pending payment (i.e., this is a POD conversion)
        $hasCodPending = $order->payments()
            ->where('payment_method', 'COD')
            ->where('status', 'Pending')
            ->exists();

        if (!$hasCodPending) {
            return;
        }

        FunnelLifecycleService::handleCodToPaidConversion(
            $order,
            $payment->created_at?->toDateTimeString() ?? now()->toDateTimeString()
        );
    }
}
