<?php

namespace App\Enums\Orders;

/**
 * Phase 3B — Refund Allocation Foundation. Extended in Phase 3C —
 * Employee-Selected Refund Sources and Multi-Source Refund Processing.
 *
 * The lifecycle of a single allocation row, independent of the refund
 * row's own OrderPaymentStatus. Today's synchronous refund flow only ever
 * creates an Allocated row (a refund row is itself only ever created after
 * the gateway call, if any, has already succeeded — see
 * RefundPaymentController), but Pending exists for a future asynchronous/
 * multi-source flow, and Failed for a flow that records a failed
 * allocation attempt without silently discarding it.
 *
 * Superseded (Phase 3C partial-failure recovery): a Failed allocation that
 * the employee chose NOT to retry against the same original payment, and
 * instead reallocated the same money to a different eligible source. The
 * old row is never deleted or silently repurposed — it is relabeled
 * Superseded so it stops counting as an ONGOING failure (excluded from
 * refund_operation_status's failed-bucket — see
 * PaymentAllocationService::syncRefundOperationOutcome() — and from the
 * presenter's "Failed:" history section) while remaining a permanent,
 * honest record that this source was attempted and abandoned in favor of
 * another. See PaymentAllocationService::supersedeAbandonedFailures().
 */
enum OrderPaymentRefundAllocationStatus: string
{
    case Pending = 'pending';
    case Allocated = 'allocated';
    case Failed = 'failed';
    case Superseded = 'superseded';

    /**
     * Whether this allocation currently ties up (reserves or has
     * permanently consumed) refundable balance on its original payment.
     * Per the Phase 3B mission: a pending allocation reserves the amount,
     * an allocated (successfully recorded) allocation consumes it, a
     * failed or superseded allocation releases it (excluded here) — both
     * Pending and Allocated are therefore "still counted against" the
     * original payment's remaining refundable amount.
     */
    public function reservesBalance(): bool
    {
        return $this === self::Pending || $this === self::Allocated;
    }
}
