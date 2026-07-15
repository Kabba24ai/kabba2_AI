<?php

namespace App\Services\Orders;

use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderPaymentRefundAllocation;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3B — Refund Allocation Foundation.
 *
 * The single authority for creating and interpreting
 * order_payment_refund_allocations rows — controllers orchestrate
 * (resolve which original payment, call the gateway, etc.), this class
 * owns the allocation math itself: validation, creation, remaining-
 * refundable calculations, parent-pointer derivation, and legacy
 * attribution resolution/backfill. No allocation-aware business logic
 * should be duplicated outside this class, the same "one canonical
 * location" discipline OrderPaymentSummary already applies to the
 * order-level payment formulas.
 *
 * Three attribution states exist for any refund row (see
 * attributionState()):
 *   - ALLOCATED: at least one allocation row exists. Authoritative.
 *   - UNALLOCATED_UNAMBIGUOUS: no allocation row yet, but exactly one
 *     settled original payment exists on the order (or the refund's own
 *     parent_order_payment_id already points at a valid original) — safe
 *     to backfill automatically.
 *   - AMBIGUOUS: no allocation row, and more than one settled original
 *     payment exists with no reliable pointer. Never guessed at — callers
 *     must treat this refund's per-payment attribution as unknown while
 *     still trusting order-level aggregates (which sum refund_amount
 *     directly and are unaffected by ambiguity).
 */
final class PaymentAllocationService
{
    public const STATE_ALLOCATED = 'allocated';

    public const STATE_UNALLOCATED_UNAMBIGUOUS = 'unallocated_unambiguous';

    public const STATE_AMBIGUOUS = 'ambiguous';

    /**
     * Record one refund → original-payment attribution. Today's refund
     * flow only ever calls this once per refund (single-source), but
     * nothing here assumes that — a future multi-source refund calls this
     * once per source payment, and syncParentPointer() below already
     * degrades correctly (null) once a second allocation exists.
     *
     * @throws \InvalidArgumentException if the base/tax split doesn't sum
     *                                    to the allocated amount, or if the
     *                                    refund/original pairing is invalid.
     */
    public static function allocateSingleSource(
        OrderPayment $refund,
        OrderPayment $original,
        float $allocatedAmount,
        float $allocatedBaseAmount,
        float $allocatedTaxAmount,
        ?float $processingFeeRetained = null,
        ?string $gatewayTransactionId = null,
        OrderPaymentRefundAllocationStatus $status = OrderPaymentRefundAllocationStatus::Allocated,
        ?string $failureReason = null,
    ): OrderPaymentRefundAllocation {
        if ($refund->order_id !== $original->order_id) {
            throw new \InvalidArgumentException(
                'PaymentAllocationService: refund payment and original payment must belong to the same order.'
            );
        }

        if ($refund->id === $original->id) {
            throw new \InvalidArgumentException(
                'PaymentAllocationService: a refund row cannot be allocated against itself.'
            );
        }

        self::validateSplit($allocatedAmount, $allocatedBaseAmount, $allocatedTaxAmount);

        $allocation = OrderPaymentRefundAllocation::create([
            'refund_order_payment_id' => $refund->id,
            'original_order_payment_id' => $original->id,
            'allocated_amount' => round($allocatedAmount, 2),
            'allocated_base_amount' => round($allocatedBaseAmount, 2),
            'allocated_tax_amount' => round($allocatedTaxAmount, 2),
            'processing_fee_retained' => $processingFeeRetained !== null ? round($processingFeeRetained, 2) : null,
            'gateway_transaction_id' => $gatewayTransactionId,
            'status' => $status->value,
            'failure_reason' => $failureReason,
        ]);

        self::syncParentPointer($refund);

        return $allocation;
    }

    /**
     * allocated_base_amount + allocated_tax_amount must equal
     * allocated_amount exactly. Compared in integer cents rather than raw
     * floats so binary floating-point noise never produces a false
     * one-cent-imbalance rejection (or, worse, silently accepts a real one).
     */
    public static function validateSplit(float $amount, float $baseAmount, float $taxAmount): void
    {
        $amountCents = (int) round($amount * 100);
        $sumCents = (int) round($baseAmount * 100) + (int) round($taxAmount * 100);

        if ($amountCents !== $sumCents) {
            throw new \InvalidArgumentException(
                "PaymentAllocationService: allocated_base_amount ({$baseAmount}) + allocated_tax_amount "
                ."({$taxAmount}) must equal allocated_amount ({$amount})."
            );
        }
    }

    /**
     * parent_order_payment_id is derived FROM the allocation table, never
     * computed independently — per the Phase 3B mission: null when zero or
     * more-than-one allocations exist, set to the single allocation's
     * original payment when exactly one exists. Uses saveQuietly() since
     * this is a derived, internal-bookkeeping write, not a user-facing
     * mutation of the refund row.
     */
    public static function syncParentPointer(OrderPayment $refund): void
    {
        $allocations = $refund->refundAllocations()->get();

        $derived = $allocations->count() === 1
            ? $allocations->first()->original_order_payment_id
            : null;

        if ($refund->parent_order_payment_id !== $derived) {
            $refund->parent_order_payment_id = $derived;
            $refund->saveQuietly();
        }
    }

    /**
     * The authoritative remaining refundable balance for one original
     * settled payment. Sources from allocations (pending + allocated
     * reserve/consume balance, failed releases it — see
     * OrderPaymentRefundAllocationStatus::reservesBalance()) once any
     * allocation exists for this payment. Falls back to the pre-Phase-3B,
     * parent-pointer-based formula only when no allocation row exists yet
     * for this payment at all — i.e. genuinely no refunds, or refunds that
     * predate Phase 3B and have not yet been backfilled — so behavior is
     * unchanged for not-yet-backfilled history rather than silently
     * treated as zero-refunded or fully-refundable.
     */
    public static function remainingRefundable(OrderPayment $original): float
    {
        if ($original->status === OrderPaymentStatus::Voided) {
            return 0.0;
        }

        if ($original->receivedRefundAllocations()->exists()) {
            $reserved = (float) $original->receivedRefundAllocations()->reserving()->sum('allocated_amount');

            return max(0.0, (float) $original->amount - $reserved);
        }

        $alreadyRefunded = (float) $original->childRefunds()
            ->whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
            ->sum('refund_amount');

        return max(0.0, (float) $original->amount - $alreadyRefunded);
    }

    /**
     * Resolve, without guessing, the one original settled payment a
     * pre-Phase-3B refund row should be attributed to:
     *   1. its existing parent_order_payment_id, if that pointer is valid
     *      (same order, not itself, not a refund/void row), or otherwise
     *   2. the order's one-and-only eligible settled original payment.
     * Returns null (ambiguous) if neither holds — never fabricates
     * attribution across a genuinely multi-payment order's history.
     */
    public static function resolveLegacyOriginalPayment(OrderPayment $refund): ?OrderPayment
    {
        if ($refund->parent_order_payment_id) {
            $parent = $refund->parentPayment;

            if ($parent
                && $parent->order_id === $refund->order_id
                && $parent->id !== $refund->id
                && !in_array($parent->status, [
                    OrderPaymentStatus::PartialRefund,
                    OrderPaymentStatus::Refund,
                    OrderPaymentStatus::Voided,
                ], true)
            ) {
                return $parent;
            }
        }

        $eligibleOriginals = OrderPayment::where('order_id', $refund->order_id)
            ->where('id', '!=', $refund->id)
            ->settled()
            ->get();

        return $eligibleOriginals->count() === 1 ? $eligibleOriginals->first() : null;
    }

    /**
     * See class docblock for the three states. UI callers use this to
     * decide whether to show a "legacy transaction" warning and whether to
     * block a per-payment refund action that depends on precise
     * attribution — order-level viewing must remain safe regardless.
     */
    public static function attributionState(OrderPayment $refund): string
    {
        if ($refund->refundAllocations()->exists()) {
            return self::STATE_ALLOCATED;
        }

        return self::resolveLegacyOriginalPayment($refund) !== null
            ? self::STATE_UNALLOCATED_UNAMBIGUOUS
            : self::STATE_AMBIGUOUS;
    }

    /**
     * Idempotent, safely re-runnable historical backfill. Only ever
     * creates an allocation when attribution is unambiguous
     * (resolveLegacyOriginalPayment() returns non-null); ambiguous
     * refunds are left untouched and reported, never fabricated. Each
     * refund is processed in its own transaction so one bad row cannot
     * abort the run for every other row — see the Artisan command for the
     * operator-facing report this return value feeds.
     *
     * newly_allocated vs. already_allocated are kept as two distinct
     * counters (rather than one combined "allocated" figure) specifically
     * so a rerun is easy to audit at a glance: a healthy rerun shows
     * newly_allocated drop to 0 while already_allocated absorbs everything
     * the previous run(s) already handled, with skipped_ambiguous and
     * errors unchanged.
     *
     * @return array{newly_allocated: int, already_allocated: int, skipped_ambiguous: int, errors: array<int, array{refund_order_payment_id: int, message: string}>}
     */
    public static function backfill(bool $dryRun = false): array
    {
        $report = [
            'newly_allocated' => 0,
            'already_allocated' => 0,
            'skipped_ambiguous' => 0,
            'errors' => [],
        ];

        $refunds = OrderPayment::whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
            ->orderBy('id')
            ->get();

        foreach ($refunds as $refund) {
            if ($refund->refundAllocations()->exists()) {
                $report['already_allocated']++;
                continue;
            }

            $original = self::resolveLegacyOriginalPayment($refund);

            if (!$original) {
                $report['skipped_ambiguous']++;
                continue;
            }

            $amount = (float) $refund->refund_amount;
            $tax = (float) $refund->tax_refunded;
            $base = round($amount - $tax, 2);

            try {
                self::validateSplit($amount, $base, $tax);

                if (!$dryRun) {
                    DB::transaction(function () use ($refund, $original, $amount, $base, $tax) {
                        self::allocateSingleSource(
                            $refund,
                            $original,
                            $amount,
                            $base,
                            $tax,
                            $refund->cc_fee_retained !== null ? (float) $refund->cc_fee_retained : null,
                            $refund->gateway_refund_id,
                            OrderPaymentRefundAllocationStatus::Allocated,
                        );
                    });
                }

                $report['newly_allocated']++;
            } catch (\Throwable $e) {
                $report['errors'][] = [
                    'refund_order_payment_id' => $refund->id,
                    'message' => $e->getMessage(),
                ];
            }
        }

        return $report;
    }
}
