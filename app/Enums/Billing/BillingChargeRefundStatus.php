<?php

namespace App\Enums\Billing;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds.
 *
 * Mirrors OrderPaymentRefundAllocationStatus's vocabulary for naming
 * consistency across the two allocation systems, but DELIBERATELY DIFFERS
 * on one point: order-level Pending allocations reserve balance (per
 * OrderPaymentRefundAllocationStatus::reservesBalance()'s explicit design —
 * a still-in-flight async gateway attempt provisionally holds the money it
 * might take). This mission's explicit requirement for Billing Charge
 * refunds is the opposite: "Pending and failed states ... must not consume
 * refundable balance." Only a status of Allocated (a synchronously
 * completed, successful refund) ever reduces remainingRefundable() here —
 * see consumesBalance() below and BillingChargeRefundService::
 * remainingRefundable(), which sums status='allocated' only, never a
 * broader "reserving" set.
 *
 * 'Superseded' is deliberately omitted: that state exists on the order-
 * level enum for a documented partial-failure retry/reallocation workflow
 * that has no equivalent here — today's CRM refund flow is a single
 * synchronous DB write with no gateway call and no retry concept (see
 * BillingChargeRefundService). Add it only if that need materializes.
 *
 * Every refund created by today's synchronous flow is Allocated within the
 * same transaction as its CustomerAccount ledger row — Pending and Failed
 * exist for consistency, auditability, and a possible future gateway-
 * mediated refund path, not because either is reachable today.
 */
enum BillingChargeRefundStatus: string
{
    case Pending = 'pending';
    case Allocated = 'allocated';
    case Failed = 'failed';

    /** Whether this allocation has actually, successfully consumed refundable balance. Only Allocated does. */
    public function consumesBalance(): bool
    {
        return $this === self::Allocated;
    }
}
