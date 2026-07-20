<?php

namespace App\Listeners;

use App\Events\Admin\Orders\RefundInitiateEvent;
use App\Models\Iam\Personnel\User;
use App\Services\Orders\RefundedOrderScheduleCloser;

/**
 * Automatic Schedule/Dispatch closure on full refund.
 *
 * RefundInitiateEvent is fired by RefundPaymentController AFTER
 * PaymentAllocationService::syncRefundOperationOutcome() finalized the
 * refund — so by the time this runs, the canonical payment state already
 * reflects the outcome. The closer re-derives eligibility from that state
 * (OrderPaymentSummary::REFUND_FULL), so partial and failed refunds are
 * no-ops here without any status inspection in this listener.
 *
 * Failures are reported, never rethrown: the refund itself has already
 * succeeded at the gateway, and a schedule-closure error must not turn a
 * durable financial success into a 500 response.
 */
class CloseSchedulesOnFullRefundListener
{
    public function handle(RefundInitiateEvent $event): void
    {
        try {
            RefundedOrderScheduleCloser::afterFullRefund(
                $event->order,
                $event->payment,
                $event->user instanceof User ? $event->user : null,
            );
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
