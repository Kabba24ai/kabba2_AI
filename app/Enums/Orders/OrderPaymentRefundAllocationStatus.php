<?php

namespace App\Enums\Orders;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * The lifecycle of a single allocation row, independent of the refund
 * row's own OrderPaymentStatus. Today's synchronous refund flow only ever
 * creates an Allocated row (a refund row is itself only ever created after
 * the gateway call, if any, has already succeeded — see
 * RefundPaymentController), but Pending exists for a future asynchronous/
 * multi-source flow, and Failed for a future flow that records a failed
 * allocation attempt without silently discarding it.
 */
enum OrderPaymentRefundAllocationStatus: string
{
    case Pending = 'pending';
    case Allocated = 'allocated';
    case Failed = 'failed';

    /**
     * Whether this allocation currently ties up (reserves or has
     * permanently consumed) refundable balance on its original payment.
     * Per the Phase 3B mission: a pending allocation reserves the amount,
     * an allocated (successfully recorded) allocation consumes it, a
     * failed allocation releases it (excluded here) — both Pending and
     * Allocated are therefore "still counted against" the original
     * payment's remaining refundable amount.
     */
    public function reservesBalance(): bool
    {
        return $this === self::Pending || $this === self::Allocated;
    }
}
