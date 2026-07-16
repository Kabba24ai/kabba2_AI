<?php

namespace App\Enums\Orders;

/**
 * Phase 3C — Employee-Selected Refund Sources and Multi-Source Refund
 * Processing.
 *
 * Tracks whether a refund row's OWN gateway/local allocation attempts all
 * succeeded — a dimension deliberately separate from the refund row's
 * OrderPaymentStatus (Failed / PartialRefund / Refund), which describes the
 * ORDER's aggregate refund completeness, not this specific operation's
 * execution outcome. See PaymentAllocationService::syncRefundOperationOutcome().
 */
enum RefundOperationStatus: string
{
    /** Row created; no allocation attempts have been recorded yet (transient — never expected to be visible outside a single in-flight request). */
    case Pending = 'pending';

    /** At least one allocation succeeded and at least one failed. Retryable. */
    case PartiallyCompleted = 'partially_completed';

    /** Every attempted allocation succeeded. */
    case Completed = 'completed';

    /** Every attempted allocation failed — nothing was actually refunded. Retryable. */
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::PartiallyCompleted => 'Partially Completed',
            self::Completed => 'Completed',
            self::Failed => 'Failed',
        };
    }

    /** Whether a retry of this refund event would have any unfinished work to do. */
    public function isRetryable(): bool
    {
        return $this === self::PartiallyCompleted || $this === self::Failed;
    }
}
