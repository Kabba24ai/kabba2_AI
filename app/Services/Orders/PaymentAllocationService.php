<?php

namespace App\Services\Orders;

use App\Enums\Orders\OrderPaymentMethod;
use App\Enums\Orders\OrderPaymentRefundAllocationStatus;
use App\Enums\Orders\OrderPaymentStatus;
use App\Enums\Orders\RefundCalculationType;
use App\Enums\Orders\RefundOperationStatus;
use App\Models\Orders\Order;
use App\Models\Orders\OrderPayment;
use App\Models\Orders\OrderPaymentRefundAllocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Phase 3B — Refund Allocation Foundation. Extended in Phase 3C —
 * Employee-Selected Refund Sources and Multi-Source Refund Processing.
 *
 * The single authority for creating and interpreting
 * order_payment_refund_allocations rows — controllers orchestrate
 * (resolve which original payment(s), call the gateway per source, etc.),
 * this class owns the allocation math itself: eligibility, suggestion,
 * validation, base/tax/fee splitting, creation, allocation-aware
 * remaining-refundable and card-fee-cap calculations, parent-pointer
 * derivation, legacy attribution resolution/backfill, and refund-operation
 * outcome tracking. No allocation-aware business logic should be
 * duplicated in Blade, JavaScript, request classes, controllers, or
 * gateway services — the same "one canonical location" discipline
 * OrderPaymentSummary already applies to the order-level payment formulas.
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
 *     directly and are unaffected by ambiguity). Phase 3C additionally
 *     BLOCKS starting any new refund on an order that has one of these —
 *     see orderHasAmbiguousRefundAttribution() — since the per-payment
 *     remaining-refundable figures every other original payment on that
 *     order reports cannot be trusted while an unattributed refund exists.
 */
final class PaymentAllocationService
{
    public const STATE_ALLOCATED = 'allocated';

    public const STATE_UNALLOCATED_UNAMBIGUOUS = 'unallocated_unambiguous';

    public const STATE_AMBIGUOUS = 'ambiguous';

    /**
     * Record one refund → original-payment attribution, or update an
     * existing not-yet-successful one in place (Phase 3C retry support —
     * see the controller's idempotent-retry handling: a failed allocation
     * attempt is retried by updating the SAME row, never by inserting a
     * second one for the same (refund, original) pair).
     *
     * Refuses outright to touch an allocation that has already succeeded
     * — an already-Allocated row is immutable from this method's
     * perspective; the only way to change course is a new, distinct
     * refund event.
     *
     * @throws \InvalidArgumentException if the base/tax/fee split doesn't sum
     *                                    to the allocated amount, or if the
     *                                    refund/original pairing is invalid.
     * @throws \LogicException if an already-Allocated row for this pair exists.
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

        $existing = OrderPaymentRefundAllocation::where('refund_order_payment_id', $refund->id)
            ->where('original_order_payment_id', $original->id)
            ->first();

        if ($existing && $existing->status === OrderPaymentRefundAllocationStatus::Allocated) {
            throw new \LogicException(
                "PaymentAllocationService: allocation for refund {$refund->id} / original {$original->id} "
                .'has already succeeded and cannot be modified.'
            );
        }

        self::validateSplit($allocatedAmount, $allocatedBaseAmount, $allocatedTaxAmount, $processingFeeRetained ?? 0.0);

        $allocation = OrderPaymentRefundAllocation::updateOrCreate(
            [
                'refund_order_payment_id' => $refund->id,
                'original_order_payment_id' => $original->id,
            ],
            [
                'allocated_amount' => round($allocatedAmount, 2),
                'allocated_base_amount' => round($allocatedBaseAmount, 2),
                'allocated_tax_amount' => round($allocatedTaxAmount, 2),
                'processing_fee_retained' => $processingFeeRetained !== null ? round($processingFeeRetained, 2) : null,
                'gateway_transaction_id' => $gatewayTransactionId,
                'status' => $status->value,
                'failure_reason' => $failureReason,
            ]
        );

        self::syncParentPointer($refund);

        return $allocation;
    }

    /**
     * allocated_base_amount + allocated_tax_amount + feeAmount must equal
     * allocated_amount exactly. feeAmount defaults to 0 — for Standard and
     * Sales Tax Only allocations (which never retain a fee), this reduces
     * to the original Phase 3B invariant (base + tax == amount). For a
     * Card Processing Fee Retained allocation, `amount` is the GROSS
     * amount drawn from the original payment (see calculateAllocationSplits())
     * and the fee is a third component alongside base/tax, not a deduction
     * applied after the fact.
     *
     * Compared in integer cents rather than raw floats so binary
     * floating-point noise never produces a false one-cent-imbalance
     * rejection (or, worse, silently accepts a real one).
     */
    public static function validateSplit(float $amount, float $baseAmount, float $taxAmount, float $feeAmount = 0.0): void
    {
        $amountCents = (int) round($amount * 100);
        $sumCents = (int) round($baseAmount * 100) + (int) round($taxAmount * 100) + (int) round($feeAmount * 100);

        if ($amountCents !== $sumCents) {
            throw new \InvalidArgumentException(
                "PaymentAllocationService: allocated_base_amount ({$baseAmount}) + allocated_tax_amount "
                ."({$taxAmount}) + fee ({$feeAmount}) must equal allocated_amount ({$amount})."
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
     * Every settled original payment on this order that still has a
     * positive remaining refundable balance, oldest first — the pool an
     * employee (or suggestAllocation()) selects/draws from. "Oldest" is by
     * id, the same ascending-creation-order convention OrderPaymentSummary
     * already uses for originalSettledPayments.
     *
     * @return Collection<int, OrderPayment>
     */
    public static function eligibleOriginalPayments(Order $order): Collection
    {
        return $order->payments()->settled()->orderBy('id')->get()
            ->filter(fn (OrderPayment $payment) => self::remainingRefundable($payment) > 0.0)
            ->values();
    }

    /**
     * Whether this order has any refund row whose original payment cannot
     * be reliably determined (see class docblock). While true, per-payment
     * remaining-refundable figures for every original payment on the order
     * are unsafe to trust for a NEW refund's validation — the order-level
     * aggregate (Order::remaining_amount, unaffected by ambiguity) remains
     * safe to display, but starting a new source-selected refund is
     * blocked entirely until the legacy row is resolved (by backfill, if
     * it becomes resolvable, or left as a permanently ambiguous historical
     * record otherwise).
     */
    public static function orderHasAmbiguousRefundAttribution(Order $order): bool
    {
        return $order->payments()->refund()->get()
            ->contains(fn (OrderPayment $refund) => self::attributionState($refund) === self::STATE_AMBIGUOUS);
    }

    /**
     * Default suggested allocation: oldest eligible original payment
     * first, then the next oldest, until the requested amount is fully
     * allocated (or eligible capacity runs out — callers should check
     * whether the returned rows actually sum to $requestedAmount before
     * treating this as satisfiable). Never processed silently — this is a
     * pure, side-effect-free computation; the employee must explicitly
     * confirm it (or their own adjustment of it) before anything is
     * submitted.
     *
     * @return array<int, array{original_order_payment_id: int, amount: float}>
     */
    public static function suggestAllocation(Order $order, float $requestedAmount): array
    {
        $remaining = round($requestedAmount, 2);
        $suggestions = [];

        foreach (self::eligibleOriginalPayments($order) as $payment) {
            if ($remaining <= 0) {
                break;
            }

            $capacity = round(self::remainingRefundable($payment), 2);
            if ($capacity <= 0) {
                continue;
            }

            $take = round(min($capacity, $remaining), 2);
            $suggestions[] = ['original_order_payment_id' => $payment->id, 'amount' => $take];
            $remaining = round($remaining - $take, 2);
        }

        return $suggestions;
    }

    /**
     * Full server-side validation of an employee-submitted (or retried)
     * allocation set — authoritative; nothing about it is trusted from the
     * client. Returns a list of human-readable error strings (empty means
     * valid). Every rule from the Phase 3C mission's "Allocation
     * Validation" section is enforced here and only here.
     *
     * For Standard and Sales Tax Only refunds, $requestedTotal is the
     * fixed total the allocation sum must exactly equal. For Card
     * Processing Fee Retained, $requestedTotal is null — that calc type's
     * total is a derived OUTPUT of calculateAllocationSplits() (gross
     * draw minus per-source fee), not a fixed input to validate against,
     * so the sum-equals-requested-total rule is skipped for it; every
     * other rule (order/payment membership, no duplicates, per-payment
     * cap, order-level cap, positive amounts, ambiguity) still applies.
     *
     * $excludingRefund is set only when re-validating a RETRY of a
     * partially-completed/failed refund event, and must be that same
     * refund row. Without it, a source that already succeeded under this
     * exact refund event would be double-counted against its own
     * remaining-refundable balance (the already-Allocated row IS part of
     * what makes that balance what it currently is) and would be wrongly
     * rejected as "exceeds remaining balance" on every retry. With it,
     * a resubmitted row that exactly matches an already-Allocated
     * allocation under this refund is trusted without re-checking capacity
     * (it isn't being re-attempted — the controller skips the gateway call
     * for it entirely); every other row is validated normally.
     *
     * @param  array<int, array{original_order_payment_id: mixed, amount: mixed}>  $allocations
     * @return array<int, string>
     */
    public static function validateAllocationSet(
        Order $order,
        array $allocations,
        ?float $requestedTotal,
        RefundCalculationType $calcType,
        ?OrderPayment $excludingRefund = null,
    ): array {
        $errors = [];

        if (empty($allocations)) {
            return ['At least one refund source must be selected.'];
        }

        $ids = array_map(fn ($a) => $a['original_order_payment_id'] ?? null, $allocations);
        if (count($ids) !== count(array_unique($ids))) {
            $errors[] = 'The same original payment was selected more than once.';
        }

        if (self::orderHasAmbiguousRefundAttribution($order)) {
            return array_merge($errors, [
                'This order has a legacy refund whose original payment cannot be determined. '
                .'New refunds are blocked until that transaction is resolved.',
            ]);
        }

        // Card Processing Fee Retained requires at least one original
        // Credit / Debit Card source in the SET — the retained fee
        // reimburses a card-processor charge, which never existed for a
        // Cash/Cheque/etc. payment. This is deliberately a set-level rule,
        // not per-source: a mixed set drawing from card AND non-card
        // originals is legal (see MultiSourceRefundTest test_15 — the
        // approved Phase 3C behavior), because calculateAllocationSplits()
        // computes the fee per source and always yields 0.0 for non-card
        // rows. Only a set with NO card source at all would fabricate a
        // processing fee out of nothing, and that is what is rejected.
        if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            $submitted = OrderPayment::whereIn('id', array_filter($ids, 'is_numeric'))->get();

            if (!$submitted->contains(fn (OrderPayment $p) => $p->payment_method === OrderPaymentMethod::Card)) {
                return array_merge($errors, [
                    'Full Amount Less Card Processing Fee is only available for an original Credit / Debit Card payment.',
                ]);
            }
        }

        $eligibleById = self::eligibleOriginalPayments($order)->keyBy('id');
        $sumCents = 0; // full resubmitted total — checked against $requestedTotal below
        $newSumCents = 0; // only rows not already successfully allocated — checked against the order's CURRENT remaining balance, which already excludes prior successes on a retry

        foreach ($allocations as $row) {
            $id = $row['original_order_payment_id'] ?? null;
            $amount = (float) ($row['amount'] ?? 0);
            $label = $id !== null ? "Refund source #{$id}" : 'A refund source';

            if ($amount <= 0) {
                $errors[] = "{$label}: amount must be greater than zero.";
                continue;
            }

            if ($excludingRefund && self::hasSuccessfulAllocation($excludingRefund, $id)) {
                // Already succeeded under this exact refund event on a
                // prior attempt — not being re-attempted, just re-confirmed
                // in the resubmission. Trust it toward the total-equality
                // sum, but it must NOT count again against the order's
                // current remaining balance (already reduced by this same
                // success) or its own payment's remaining balance (same
                // reason) — see $newSumCents below.
                $sumCents += (int) round($amount * 100);
                continue;
            }

            /** @var OrderPayment|null $payment */
            $payment = $eligibleById->get($id);

            if (!$payment) {
                $errors[] = self::describeIneligibility($order, $id, $label);
                continue;
            }

            $remaining = round(self::remainingRefundable($payment), 2);
            if (round($amount, 2) > $remaining + 0.005) {
                $errors[] = "{$label}: amount (\${$amount}) exceeds its remaining refundable balance (\${$remaining}).";
                continue;
            }

            $sumCents += (int) round($amount * 100);
            $newSumCents += (int) round($amount * 100);
        }

        if (!empty($errors)) {
            return $errors;
        }

        $orderRemainingCents = (int) round((float) $order->remaining_amount * 100);
        if ($newSumCents > $orderRemainingCents + 1) {
            $errors[] = 'The total selected exceeds this order\'s remaining refundable balance ($'
                .number_format($orderRemainingCents / 100, 2).').';
        }

        if ($requestedTotal !== null) {
            $requestedCents = (int) round($requestedTotal * 100);
            if ($sumCents !== $requestedCents) {
                $errors[] = 'The sum of refund sources ($'.number_format($sumCents / 100, 2)
                    .') must equal the requested refund amount ($'.number_format($requestedTotal, 2).').';
            }
        }

        return $errors;
    }

    /** Whether $refund already has a successful (Allocated) allocation against original payment $originalId. */
    public static function hasSuccessfulAllocation(OrderPayment $refund, mixed $originalId): bool
    {
        return $refund->refundAllocations()
            ->where('original_order_payment_id', $originalId)
            ->where('status', OrderPaymentRefundAllocationStatus::Allocated->value)
            ->exists();
    }

    /** Why a submitted original_order_payment_id failed to resolve to an eligible source — for a clear, per-payment validation message. */
    private static function describeIneligibility(Order $order, mixed $id, string $label): string
    {
        $payment = is_numeric($id) ? OrderPayment::find($id) : null;

        if (!$payment) {
            return "{$label}: payment not found.";
        }

        if ($payment->order_id !== $order->id) {
            return "{$label}: this payment does not belong to this order.";
        }

        if (in_array($payment->status, [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund], true)) {
            return "{$label}: a refund row cannot itself be selected as a refund source.";
        }

        if ($payment->status === OrderPaymentStatus::Voided) {
            return "{$label}: this payment was voided and cannot be refunded.";
        }

        if ($payment->status === OrderPaymentStatus::Failed || $payment->status === OrderPaymentStatus::Pending) {
            return "{$label}: only settled original payments can be refunded.";
        }

        return "{$label}: no remaining refundable balance.";
    }

    /**
     * Resolves the FIXED requested total for calc types that have one —
     * moved here from RefundPaymentController (Phase 3D) so both the real
     * refund endpoint and the preview endpoint share the exact same
     * resolution, rather than risking the two drifting apart. Standard
     * uses the client-submitted amount; Sales Tax Only computes the
     * order's remaining refundable sales tax server-side; Card Processing
     * Fee Retained has no single fixed total to resolve up front — its
     * total is a derived OUTPUT of calculateAllocationSplits() (gross draw
     * per source minus that source's own capped fee), so this returns
     * null for it and validateAllocationSet() skips the
     * sum-equals-requested-total rule for that case.
     *
     * @return array{0: ?float, 1: ?string} [requestedTotal, errorMessage]
     */
    public static function resolveRequestedTotal(Order $order, RefundCalculationType $calcType, float $clientAmount): array
    {
        if ($calcType === RefundCalculationType::SalesTaxOnly) {
            // Payment Architecture Finalization — Sales Tax Refund
            // Write-Path Correction: was a raw, allocation-unaware
            // sum('tax_refunded') across every PartialRefund/Refund row —
            // the same defect the Final Read-Side Cleanup already fixed
            // for the refund modal's display-only figure, except THIS
            // branch actually drives eligibility, the previewed amount,
            // and the submitted/executed amount (both
            // RefundPaymentController and RefundPaymentPreviewController
            // call this method). Now sourced from
            // totalSuccessfulRefundedTax() — Allocated-only, multi-source-
            // safe, falls back to the legacy raw column only when an order
            // has zero allocation rows at all — the exact same canonical
            // figure the display now shows, so the two can never disagree.
            $alreadyRefundedTax = self::totalSuccessfulRefundedTax($order);

            $remainingRefundableTax = max(0.0, round((float) $order->tax_amount - $alreadyRefundedTax, 2));
            $remainingRefundableTax = round(min($remainingRefundableTax, (float) $order->remaining_amount), 2);

            if ($remainingRefundableTax <= 0) {
                return [null, 'No refundable sales tax remains for this order.'];
            }

            return [$remainingRefundableTax, null];
        }

        if ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            return [null, null];
        }

        if ($clientAmount <= 0) {
            return [null, 'Refund amount must be greater than zero.'];
        }

        return [$clientAmount, null];
    }

    /**
     * The pre-existing proportional tax-extraction formula (unchanged
     * since Phase 2/3A) — moved here from RefundPaymentController so the
     * one piece of tax-split math used by both Standard and Card
     * Processing Fee Retained allocations lives in the allocation service,
     * not duplicated in a controller.
     */
    public static function proportionalTaxRefund(Order $order, float $refundAmount): float
    {
        $originalTaxRate = ((float) $order->subtotal > 0 && (float) $order->tax_amount > 0)
            ? (float) $order->tax_amount / (float) $order->subtotal
            : 0.0;

        return $originalTaxRate > 0
            ? round($refundAmount - ($refundAmount / (1 + $originalTaxRate)), 2)
            : 0.0;
    }

    /**
     * Computes, for an already-VALIDATED allocation set (call
     * validateAllocationSet() first — this method does not re-validate),
     * the base/tax/fee split for every row plus the resulting aggregate
     * totals. This is the one place that decides HOW a total is broken
     * down per calc type — Standard and Sales Tax Only never duplicate the
     * split logic elsewhere, and Card Processing Fee Retained's per-source
     * fee (capped per original card payment's lifetime allowance — see
     * remainingCardFeeCapacity()) is computed here, once.
     *
     * `amount` on every returned row is the GROSS amount drawn from that
     * original payment (what reduces its remaining refundable balance).
     * For Card Processing Fee Retained rows, the customer-facing NET
     * amount is `amount - fee`; total_net below is the sum of that across
     * every row and is what belongs in the refund row's own
     * `refund_amount` for that calc type (see RefundPaymentController).
     *
     * Rounding is deterministic: proportional per row, with any final
     * one-cent remainder assigned to the LAST row in $allocations (in
     * submitted order), so per-row sums always match the row's own
     * `amount` exactly, and aggregate sums always match the requested
     * total exactly.
     *
     * @param  Collection<int, OrderPayment>  $originalsById  keyed by id — every original payment referenced in $allocations
     * @param  array<int, array{original_order_payment_id: int, amount: float}>  $allocations
     * @return array{
     *     rows: array<int, array{original_order_payment_id: int, amount: float, base: float, tax: float, fee: float}>,
     *     total_amount: float, total_base: float, total_tax: float, total_fee: float, total_net: float
     * }
     */
    public static function calculateAllocationSplits(
        Order $order,
        Collection $originalsById,
        array $allocations,
        RefundCalculationType $calcType,
        float $feePercentage = 0.0,
    ): array {
        $rows = [];

        if ($calcType === RefundCalculationType::SalesTaxOnly) {
            foreach ($allocations as $row) {
                $amount = round((float) $row['amount'], 2);
                $rows[] = [
                    'original_order_payment_id' => $row['original_order_payment_id'],
                    'amount' => $amount, 'base' => 0.0, 'tax' => $amount, 'fee' => 0.0,
                ];
            }
        } elseif ($calcType === RefundCalculationType::CardProcessingFeeRetained) {
            foreach ($allocations as $row) {
                $original = $originalsById->get($row['original_order_payment_id']);
                $gross = round((float) $row['amount'], 2);
                $isCard = $original && $original->payment_method === OrderPaymentMethod::Card;

                $fee = 0.0;
                if ($isCard && $feePercentage > 0) {
                    $rawFee = round($gross * $feePercentage / 100, 2);
                    $cap = self::remainingCardFeeCapacity($original, $feePercentage);
                    $fee = round(min($rawFee, $cap), 2);
                }

                $net = round($gross - $fee, 2);
                $tax = self::proportionalTaxRefund($order, $net);
                $base = round($net - $tax, 2);

                $rows[] = [
                    'original_order_payment_id' => $row['original_order_payment_id'],
                    'amount' => $gross, 'base' => $base, 'tax' => $tax, 'fee' => $fee,
                ];
            }
        } else {
            // Standard — proportional base/tax split of the combined total,
            // deterministic remainder-to-last-row rounding.
            $totalAmountCents = 0;
            foreach ($allocations as $row) {
                $totalAmountCents += (int) round((float) $row['amount'] * 100);
            }

            $totalTax = self::proportionalTaxRefund($order, round($totalAmountCents / 100, 2));
            $totalTaxCents = (int) round($totalTax * 100);
            $runningTaxCents = 0;
            $count = count($allocations);

            foreach (array_values($allocations) as $i => $row) {
                $amount = round((float) $row['amount'], 2);
                $amountCents = (int) round($amount * 100);

                if ($i === $count - 1) {
                    $taxCents = $totalTaxCents - $runningTaxCents;
                } else {
                    $taxCents = $totalAmountCents > 0
                        ? (int) round($totalTaxCents * $amountCents / $totalAmountCents)
                        : 0;
                    $runningTaxCents += $taxCents;
                }

                $tax = round($taxCents / 100, 2);
                $base = round($amount - $tax, 2);

                $rows[] = [
                    'original_order_payment_id' => $row['original_order_payment_id'],
                    'amount' => $amount, 'base' => $base, 'tax' => $tax, 'fee' => 0.0,
                ];
            }
        }

        $totalAmount = round(array_sum(array_column($rows, 'amount')), 2);
        $totalBase = round(array_sum(array_column($rows, 'base')), 2);
        $totalTax = round(array_sum(array_column($rows, 'tax')), 2);
        $totalFee = round(array_sum(array_column($rows, 'fee')), 2);

        return [
            'rows' => $rows,
            'total_amount' => $totalAmount,
            'total_base' => $totalBase,
            'total_tax' => $totalTax,
            'total_fee' => $totalFee,
            'total_net' => round($totalAmount - $totalFee, 2),
        ];
    }

    /**
     * The Card Processing Fee Retained lifetime cap for ONE original card
     * payment: Configured Fee Percentage × Original Card Payment Amount,
     * minus every fee already retained against it by a successful
     * allocation, across every refund event — never per-refund, always
     * cumulative for the lifetime of that original payment.
     */
    public static function remainingCardFeeCapacity(OrderPayment $original, float $feePercentage): float
    {
        if ($feePercentage <= 0) {
            return 0.0;
        }

        $cap = round((float) $original->amount * $feePercentage / 100, 2);

        $consumed = (float) $original->receivedRefundAllocations()
            ->where('status', OrderPaymentRefundAllocationStatus::Allocated->value)
            ->sum('processing_fee_retained');

        return max(0.0, round($cap - $consumed, 2));
    }

    /**
     * Order-level "total successfully refunded" — Phase 3C correctness
     * fix for Order::getTotalRefundedAttribute(): counts only the
     * customer-facing NET portion (allocated_amount minus any retained
     * fee) of allocations that actually succeeded, never the requested/
     * intended total sitting on a refund row that partially or fully
     * failed. Falls back to a refund row's raw refund_amount only when it
     * has no allocations at all yet (legacy, not backfilled) — unchanged
     * pre-Phase-3B behavior for that narrow case, consistent with every
     * other legacy fallback in this class.
     */
    public static function totalSuccessfulRefunded(Order $order): float
    {
        $refunds = $order->payments()->refund()->with('refundAllocations')->get();

        $total = 0.0;
        foreach ($refunds as $refund) {
            if ($refund->refundAllocations->isNotEmpty()) {
                $total += (float) $refund->refundAllocations
                    ->where('status', OrderPaymentRefundAllocationStatus::Allocated)
                    ->sum(fn (OrderPaymentRefundAllocation $a) => (float) $a->allocated_amount - (float) ($a->processing_fee_retained ?? 0));
            } else {
                $total += (float) $refund->refund_amount;
            }
        }

        return round($total, 2);
    }

    /**
     * Order-level "total sales tax successfully refunded" — the
     * allocation-aware counterpart to totalSuccessfulRefunded() for the tax
     * portion specifically. Payment Architecture Finalization — Final
     * Read-Side Cleanup: replaces a raw, unguarded
     * `sum('tax_refunded')` across every PartialRefund/Refund row that
     * previously lived inline in edit.blade.php (the refund modal's
     * "previously refunded tax" display), the one allocation-unaware
     * tax-refund read the Phase 4B audit found. Mirrors
     * totalSuccessfulRefunded()'s exact structure — same query, same
     * Allocated-only filter, same legacy-fallback shape — so this is a
     * direct structural copy for the tax column, not a new formula.
     * Correctly nets sales-tax-only refunds (their allocated_tax_amount
     * equals the full allocated amount, which is exactly what should count
     * here) and multi-source refunds (every allocation row across every
     * refund event on the order is summed). Falls back to a refund row's
     * raw tax_refunded only when it has no allocations at all yet (legacy,
     * not backfilled) — a backfilled row has real Allocated allocation
     * rows with a correct allocated_tax_amount and takes the primary
     * branch, same as every other legacy fallback in this class.
     */
    public static function totalSuccessfulRefundedTax(Order $order): float
    {
        $refunds = $order->payments()->refund()->with('refundAllocations')->get();

        $total = 0.0;
        foreach ($refunds as $refund) {
            if ($refund->refundAllocations->isNotEmpty()) {
                $total += (float) $refund->refundAllocations
                    ->where('status', OrderPaymentRefundAllocationStatus::Allocated)
                    ->sum(fn (OrderPaymentRefundAllocation $a) => (float) $a->allocated_tax_amount);
            } else {
                $total += (float) $refund->tax_refunded;
            }
        }

        return round($total, 2);
    }

    /**
     * Order-wide sum of card-processing fees retained across every
     * successfully-allocated refund on this order — the one figure Phase
     * 3D's Order Details / receipt summaries need that no existing
     * accessor already provides. Only Allocated allocations count (a
     * Failed or Superseded attempt never actually retained anything); rows
     * with no fee (Standard/Sales Tax Only allocations, or non-card
     * sources under Card Processing Fee Retained) contribute zero via
     * processing_fee_retained's null coalesce, same convention as
     * totalSuccessfulRefunded() above.
     */
    public static function totalFeesRetained(Order $order): float
    {
        $refunds = $order->payments()->refund()->with('refundAllocations')->get();

        $total = 0.0;
        foreach ($refunds as $refund) {
            $total += (float) $refund->refundAllocations
                ->where('status', OrderPaymentRefundAllocationStatus::Allocated)
                ->sum(fn (OrderPaymentRefundAllocation $a) => (float) ($a->processing_fee_retained ?? 0));
        }

        return round($total, 2);
    }

    /**
     * Order-wide "total refund requested" — the gross sum of every
     * allocation row regardless of outcome (Allocated, Failed, or
     * Pending; Superseded is excluded since that money's intent has
     * already been reassigned to its replacement allocation, which is
     * itself counted). This is deliberately distinct from
     * totalSuccessfulRefunded() (completed only) and outstandingRefundAmount()
     * (failed-and-unresolved only) — together the three give the Order
     * Details summary its Requested / Completed / Outstanding triplet.
     * Falls back to a refund row's raw refund_amount when it has no
     * allocations yet (legacy, not backfilled), matching every other
     * legacy fallback in this class.
     */
    public static function totalRequestedRefund(Order $order): float
    {
        $refunds = $order->payments()->refund()->with('refundAllocations')->get();

        $total = 0.0;
        foreach ($refunds as $refund) {
            if ($refund->refundAllocations->isNotEmpty()) {
                $total += (float) $refund->refundAllocations
                    ->whereIn('status', [
                        OrderPaymentRefundAllocationStatus::Allocated,
                        OrderPaymentRefundAllocationStatus::Failed,
                        OrderPaymentRefundAllocationStatus::Pending,
                    ])
                    ->sum(fn (OrderPaymentRefundAllocation $a) => (float) $a->allocated_amount);
            } else {
                $total += (float) $refund->refund_amount;
            }
        }

        return round($total, 2);
    }

    /**
     * Phase 3C partial-failure recovery: relabels every Failed allocation
     * under $refund whose original payment is NOT part of the current
     * resubmission as Superseded — i.e. the employee chose to reallocate
     * that money to a different source rather than retry the same one.
     *
     * Never deletes or repurposes the row (the "we tried Card B, it
     * failed" record is permanent, honest history); only its status
     * changes, so it stops counting as an ONGOING failure in
     * syncRefundOperationOutcome() and the presenter's history rendering,
     * while remaining visible on request. A Failed allocation whose
     * original payment IS still present in the resubmission is left alone
     * — that's a same-source retry, handled by allocateSingleSource()'s
     * normal updateOrCreate.
     *
     * Idempotent and safe to call on every retry attempt, including the
     * first (a fresh refund has no Failed rows yet, so this is a no-op).
     *
     * @param  int[]  $submittedOriginalPaymentIds
     */
    public static function supersedeAbandonedFailures(OrderPayment $refund, array $submittedOriginalPaymentIds): void
    {
        $refund->refundAllocations()
            ->where('status', OrderPaymentRefundAllocationStatus::Failed->value)
            ->whereNotIn('original_order_payment_id', $submittedOriginalPaymentIds)
            ->get()
            ->each(function (OrderPaymentRefundAllocation $allocation) {
                $allocation->update([
                    'status' => OrderPaymentRefundAllocationStatus::Superseded->value,
                    'failure_reason' => trim(
                        ($allocation->failure_reason ? $allocation->failure_reason.' — ' : '')
                        .'reallocated to a different source by the employee'
                    ),
                ]);
            });
    }

    /**
     * Whether a Failed allocation is the rare "gateway call succeeded but
     * could not be recorded" case (see RefundPaymentController's
     * documented persistence sequence) rather than an ordinary, safely
     * retryable decline. There is no dedicated column for this — it is
     * reconstructed from the distinctive marker text
     * RefundPaymentController writes into failure_reason for exactly this
     * case, which keeps the schema unchanged for what should be a very
     * rare condition while still letting a page reload (not just the
     * original request/response) correctly refuse to offer "retry" for
     * it — see the Order Details incomplete-refund banner.
     */
    public static function allocationNeedsManualReview(OrderPaymentRefundAllocation $allocation): bool
    {
        return $allocation->status === OrderPaymentRefundAllocationStatus::Failed
            && str_contains((string) $allocation->failure_reason, 'could not be recorded');
    }

    /**
     * The amount of a refund event that is still outstanding — requested
     * but neither successfully refunded nor currently in flight. Sums the
     * net (allocated_amount minus any retained fee) of every CURRENTLY
     * Failed allocation under $refund — Superseded ones are deliberately
     * excluded (that money's fate has already been reassigned to a
     * different, still-tracked allocation, so counting both would
     * double-count the same dollar). Zero once every allocation is either
     * Allocated or Superseded — i.e. once refund_operation_status reads
     * Completed, or every failure has been reallocated elsewhere.
     *
     * This is the figure the Order Details "Refund Incomplete" banner
     * displays — see incompleteRefunds() — rather than the bare
     * refund_amount, which represents intent, not what's still unresolved.
     */
    public static function outstandingRefundAmount(OrderPayment $refund): float
    {
        $failed = $refund->refundAllocations()
            ->where('status', OrderPaymentRefundAllocationStatus::Failed->value)
            ->get();

        return round(
            (float) $failed->sum(fn (OrderPaymentRefundAllocation $a) => (float) $a->allocated_amount - (float) ($a->processing_fee_retained ?? 0)),
            2
        );
    }

    /**
     * Every refund event on this order that is not fully resolved
     * (PartiallyCompleted or Failed), most recent first — the data source
     * for the Order Details "Refund Incomplete" banner. Each row's own
     * outstandingRefundAmount() is what should be displayed, never a bare
     * "Partially Completed" label on its own (Phase 3C mission — Order
     * Details visibility requirement).
     *
     * @return Collection<int, OrderPayment>
     */
    public static function incompleteRefunds(Order $order): Collection
    {
        return $order->payments()->refund()
            ->whereIn('refund_operation_status', [
                RefundOperationStatus::PartiallyCompleted->value,
                RefundOperationStatus::Failed->value,
            ])
            ->with('refundAllocations.originalPayment')
            ->latest('id')
            ->get();
    }

    /**
     * Recomputes and persists a refund row's OrderPaymentStatus and
     * RefundOperationStatus from its allocations' actual outcomes — the
     * single place either field is derived after gateway/local attempts
     * resolve. $remainingBeforeOperation must be the order's refundable
     * balance captured BEFORE this refund event's own allocations were
     * attempted (the controller captures this once, up front); this keeps
     * the "will the order still have money left to refund" question
     * anchored to the true starting point rather than a value already
     * mutated by this same operation's own partial success.
     *
     * See RefundOperationStatus for the field's four values and
     * OrderPaymentStatus for why Failed/PartialRefund/Refund remain the
     * right vocabulary for the order-level dimension — a refund event
     * where NOTHING succeeded is accurately OrderPaymentStatus::Failed
     * (already excluded from scopeRefund() and scopeSettled(), so it can
     * never be mistaken for a real refund or a real payment elsewhere).
     *
     * $failed below only matches CURRENTLY Failed allocations — a
     * Superseded one (see supersedeAbandonedFailures()) is a distinct enum
     * value and is excluded automatically, so a refund event where every
     * originally-failed source has since been successfully reallocated
     * elsewhere correctly reaches Completed, not stuck at
     * PartiallyCompleted forever.
     *
     * The both-empty branch below guards a narrow edge case Superseded
     * introduces: if $succeeded and $failed are BOTH empty (every
     * allocation under this refund is Superseded, or — defensively —
     * still Pending) there is currently nothing to call a success, so
     * this must resolve to Failed, never fall through to the "no Failed
     * rows exist" check and be misread as Completed. In the normal
     * request lifecycle this state is never actually observed here —
     * supersedeAbandonedFailures() only runs immediately before the
     * per-source attempt loop re-processes whatever it just superseded,
     * so by the time this method runs at the end of the same request,
     * every superseded original has a fresh Allocated or Failed row
     * alongside it — but the check costs nothing and removes the
     * possibility entirely rather than relying on that ordering forever.
     */
    public static function syncRefundOperationOutcome(OrderPayment $refund, float $remainingBeforeOperation): void
    {
        $allocations = $refund->refundAllocations()->get();

        $succeeded = $allocations->where('status', OrderPaymentRefundAllocationStatus::Allocated);
        $failed = $allocations->where('status', OrderPaymentRefundAllocationStatus::Failed);

        $actualSucceededTotal = (float) $succeeded->sum('allocated_amount');

        $operationStatus = match (true) {
            $allocations->isEmpty() => RefundOperationStatus::Pending,
            $succeeded->isEmpty() && $failed->isEmpty() => RefundOperationStatus::Failed,
            $failed->isEmpty() => RefundOperationStatus::Completed,
            $succeeded->isEmpty() => RefundOperationStatus::Failed,
            default => RefundOperationStatus::PartiallyCompleted,
        };

        $willRemain = round($remainingBeforeOperation - $actualSucceededTotal, 2);

        $status = match (true) {
            $actualSucceededTotal <= 0.0 => OrderPaymentStatus::Failed,
            $willRemain <= 0.0 => OrderPaymentStatus::Refund,
            default => OrderPaymentStatus::PartialRefund,
        };

        $refund->status = $status;
        $refund->refund_operation_status = $operationStatus;
        $refund->saveQuietly();
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
     * Phase 3C note: a legacy Card Processing Fee Retained refund row
     * stored refund_amount as the NET (post-fee) amount, with the fee
     * tracked separately in cc_fee_retained — under Phase 3C's allocation
     * semantics, allocated_amount must be the GROSS amount drawn from the
     * original payment (net + fee), so backfill reconstructs that gross
     * figure here rather than reusing refund_amount directly. This also
     * retroactively fixes the same "phantom remaining balance" defect
     * Phase 3C fixes going forward — see the Phase 3C deliverable for
     * details. base/tax are unaffected (base = net − tax, exactly as
     * originally computed).
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

            $net = (float) $refund->refund_amount;
            $fee = $refund->refund_calculation_type === RefundCalculationType::CardProcessingFeeRetained
                ? (float) ($refund->cc_fee_retained ?? 0)
                : 0.0;
            $amount = round($net + $fee, 2);
            $tax = (float) $refund->tax_refunded;
            $base = round($net - $tax, 2);

            try {
                self::validateSplit($amount, $base, $tax, $fee);

                if (!$dryRun) {
                    DB::transaction(function () use ($refund, $original, $amount, $base, $tax, $fee) {
                        self::allocateSingleSource(
                            $refund,
                            $original,
                            $amount,
                            $base,
                            $tax,
                            $fee > 0 ? $fee : null,
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

    /**
     * Phase 3D — a read-only integrity sweep of the whole allocation
     * schema, for the `payments:audit` command. Every check here reuses an
     * existing authoritative primitive from this class (attributionState(),
     * remainingRefundable(), validateSplit(), incompleteRefunds()) rather
     * than re-deriving its own notion of correctness — this method only
     * counts and reports, it never writes anything.
     *
     * 'summary' entries are informational counts (expected to be non-zero
     * in normal operation — e.g. ambiguous_legacy_refunds is a known,
     * permanent historical condition, not a bug). 'issues' entries are
     * genuine integrity failures — each one indicates something that
     * should never happen if every write path in this class is behaving
     * correctly — and drive has_integrity_failures, which the Artisan
     * command uses for its exit code.
     *
     * @return array{summary: array<string, int>, issues: array<string, array<int, array<string, mixed>>>, has_integrity_failures: bool}
     */
    public static function auditIntegrity(): array
    {
        $summary = [
            'refund_events' => 0,
            'successful_allocations' => 0,
            'pending_allocations' => 0,
            'failed_allocations' => 0,
            'superseded_allocations' => 0,
            'unallocated_refunds' => 0,
            'ambiguous_legacy_refunds' => 0,
            'incomplete_refund_operations' => 0,
            'broken_parent_pointers' => 0,
            'allocation_total_mismatches' => 0,
            'split_mismatches' => 0,
            'refunds_exceeding_original' => 0,
            'negative_remaining_balances' => 0,
            'operation_status_disagreements' => 0,
            'orphan_allocations' => 0,
            'duplicate_gateway_refund_ids' => 0,
            'duplicate_idempotency_tokens' => 0,
            'fee_exceeds_lifetime_max' => 0,
            'negative_refunded_tax' => 0,
        ];

        $issues = [
            'broken_parent_pointers' => [],
            'allocation_total_mismatches' => [],
            'split_mismatches' => [],
            'refunds_exceeding_original' => [],
            'negative_remaining_balances' => [],
            'operation_status_disagreements' => [],
            'orphan_allocations' => [],
            'duplicate_gateway_refund_ids' => [],
            'duplicate_idempotency_tokens' => [],
            'fee_exceeds_lifetime_max' => [],
            'negative_refunded_tax' => [],
        ];

        $summary['successful_allocations'] = OrderPaymentRefundAllocation::where('status', OrderPaymentRefundAllocationStatus::Allocated->value)->count();
        $summary['pending_allocations'] = OrderPaymentRefundAllocation::where('status', OrderPaymentRefundAllocationStatus::Pending->value)->count();
        $summary['failed_allocations'] = OrderPaymentRefundAllocation::where('status', OrderPaymentRefundAllocationStatus::Failed->value)->count();
        $summary['superseded_allocations'] = OrderPaymentRefundAllocation::where('status', OrderPaymentRefundAllocationStatus::Superseded->value)->count();

        $refunds = OrderPayment::whereIn('status', [OrderPaymentStatus::PartialRefund, OrderPaymentStatus::Refund])
            ->with('refundAllocations.originalPayment', 'order')
            ->orderBy('id')
            ->get();

        $summary['refund_events'] = $refunds->count();

        foreach ($refunds as $refund) {
            $allocations = $refund->refundAllocations;

            if ($allocations->isEmpty()) {
                $summary['unallocated_refunds']++;

                if (self::attributionState($refund) === self::STATE_AMBIGUOUS) {
                    $summary['ambiguous_legacy_refunds']++;
                }
            }

            // Parent pointer must equal syncParentPointer()'s derivation:
            // null unless exactly one allocation row exists.
            $expectedParent = $allocations->count() === 1 ? $allocations->first()->original_order_payment_id : null;
            if ($allocations->isNotEmpty() && $refund->parent_order_payment_id !== $expectedParent) {
                $summary['broken_parent_pointers']++;
                $issues['broken_parent_pointers'][] = [
                    'refund_order_payment_id' => $refund->id,
                    'order_id' => $refund->order_id,
                    'expected' => $expectedParent,
                    'actual' => $refund->parent_order_payment_id,
                ];
            }

            // For every allocation row, base + tax + fee must equal amount.
            foreach ($allocations as $allocation) {
                try {
                    self::validateSplit(
                        (float) $allocation->allocated_amount,
                        (float) $allocation->allocated_base_amount,
                        (float) $allocation->allocated_tax_amount,
                        (float) ($allocation->processing_fee_retained ?? 0)
                    );
                } catch (\InvalidArgumentException $e) {
                    $summary['split_mismatches']++;
                    $issues['split_mismatches'][] = [
                        'allocation_id' => $allocation->id,
                        'refund_order_payment_id' => $refund->id,
                        'order_id' => $refund->order_id,
                        'message' => $e->getMessage(),
                    ];
                }
            }

            // The refund row's own recorded refund_amount should match the
            // net (fee-excluded) sum of its successfully-Allocated rows.
            if ($allocations->isNotEmpty()) {
                $succeeded = $allocations->where('status', OrderPaymentRefundAllocationStatus::Allocated);
                if ($succeeded->isNotEmpty()) {
                    $netAllocated = round((float) $succeeded->sum(
                        fn (OrderPaymentRefundAllocation $a) => (float) $a->allocated_amount - (float) ($a->processing_fee_retained ?? 0)
                    ), 2);
                    $recorded = round((float) $refund->refund_amount, 2);

                    if (abs((int) round($netAllocated * 100) - (int) round($recorded * 100)) > 1) {
                        $summary['allocation_total_mismatches']++;
                        $issues['allocation_total_mismatches'][] = [
                            'refund_order_payment_id' => $refund->id,
                            'order_id' => $refund->order_id,
                            'net_allocated' => $netAllocated,
                            'refund_amount' => $recorded,
                        ];
                    }
                }
            }

            if (in_array($refund->refund_operation_status, [
                RefundOperationStatus::PartiallyCompleted,
                RefundOperationStatus::Failed,
            ], true)) {
                $summary['incomplete_refund_operations']++;
            }

            // A refund marked Completed must have at least one Allocated
            // allocation and zero currently-Failed ones — the exact
            // condition syncRefundOperationOutcome() itself requires to
            // ever set Completed in the first place. Finding a mismatch
            // here means either that method was bypassed, or a row was
            // hand-edited after the fact.
            if ($refund->refund_operation_status === RefundOperationStatus::Completed) {
                $succeededCount = $allocations->where('status', OrderPaymentRefundAllocationStatus::Allocated)->count();
                $failedCount = $allocations->where('status', OrderPaymentRefundAllocationStatus::Failed)->count();

                if ($succeededCount === 0 || $failedCount > 0) {
                    $summary['operation_status_disagreements']++;
                    $issues['operation_status_disagreements'][] = [
                        'refund_order_payment_id' => $refund->id,
                        'order_id' => $refund->order_id,
                        'allocated_count' => $succeededCount,
                        'failed_count' => $failedCount,
                    ];
                }
            }

            if ((float) $refund->tax_refunded < -0.005) {
                $summary['negative_refunded_tax']++;
                $issues['negative_refunded_tax'][] = [
                    'refund_order_payment_id' => $refund->id,
                    'order_id' => $refund->order_id,
                    'tax_refunded' => (float) $refund->tax_refunded,
                ];
            }

            foreach ($allocations as $allocation) {
                if ((float) $allocation->allocated_tax_amount < -0.005) {
                    $summary['negative_refunded_tax']++;
                    $issues['negative_refunded_tax'][] = [
                        'allocation_id' => $allocation->id,
                        'refund_order_payment_id' => $refund->id,
                        'order_id' => $refund->order_id,
                        'allocated_tax_amount' => (float) $allocation->allocated_tax_amount,
                    ];
                }
            }
        }

        // Orphan allocations: a row whose parent refund event no longer has
        // a Refund/PartialRefund status (its own status query already
        // excludes these from the loop above), or whose original/refund
        // payment relation cannot resolve at all — defensive checks beyond
        // what the schema's own foreign keys already guarantee, in case of
        // a hand-edited row or a status hand-reverted after allocation.
        $orphanAllocations = OrderPaymentRefundAllocation::whereDoesntHave('refundPayment', function ($q) {
            $q->whereIn('status', [OrderPaymentStatus::PartialRefund->value, OrderPaymentStatus::Refund->value]);
        })->orWhereDoesntHave('originalPayment')->with('refundPayment', 'originalPayment')->get();

        foreach ($orphanAllocations as $orphan) {
            $summary['orphan_allocations']++;
            $issues['orphan_allocations'][] = [
                'allocation_id' => $orphan->id,
                'refund_order_payment_id' => $orphan->refund_order_payment_id,
                'original_order_payment_id' => $orphan->original_order_payment_id,
                'refund_status' => $orphan->refundPayment?->status?->value,
            ];
        }

        // Duplicate gateway refund IDs: two different Allocated allocations
        // referencing the same gateway transaction id would mean the same
        // gateway refund was recorded twice against two sources.
        OrderPaymentRefundAllocation::where('status', OrderPaymentRefundAllocationStatus::Allocated->value)
            ->whereNotNull('gateway_transaction_id')
            ->get()
            ->groupBy('gateway_transaction_id')
            ->filter(fn ($group) => $group->count() > 1)
            ->each(function ($group, $gatewayId) use (&$summary, &$issues) {
                $summary['duplicate_gateway_refund_ids']++;
                $issues['duplicate_gateway_refund_ids'][] = [
                    'gateway_transaction_id' => $gatewayId,
                    'allocation_ids' => $group->pluck('id')->all(),
                ];
            });

        // Duplicate idempotency tokens — already enforced by a DB-level
        // UNIQUE constraint (migration 2026_07_14_130000), so this should
        // never fire in practice; kept as a defense-in-depth check per the
        // mission's explicit ask.
        OrderPayment::whereNotNull('idempotency_token')
            ->select('idempotency_token', 'id', 'order_id')
            ->get()
            ->groupBy('idempotency_token')
            ->filter(fn ($group) => $group->count() > 1)
            ->each(function ($group, $token) use (&$summary, &$issues) {
                $summary['duplicate_idempotency_tokens']++;
                $issues['duplicate_idempotency_tokens'][] = [
                    'idempotency_token' => $token,
                    'refund_order_payment_ids' => $group->pluck('id')->all(),
                ];
            });

        // Currently-configured fee percentage — used only to flag an
        // allocation's cumulative retained fee against TODAY's cap. If this
        // percentage has changed since some of these allocations were
        // created, a historically-correct allocation (capped under a prior
        // percentage) could show here; treat a finding against this check
        // as a prompt to review, not an automatic proof of a bug, the same
        // caveat remainingCardFeeCapacity() itself is subject to since it
        // also always evaluates against the CURRENT configured percentage.
        $currentFeePercentage = (float) (\App\Helpers\ConfigurationHelper::getSettings('Product Settings', 'credit_card_processing_fee') ?? 0);

        // Per original payment: reserved (pending + allocated) amount must
        // never exceed what was ever collected on that payment, and total
        // retained fee must never exceed the configured lifetime cap.
        $originals = OrderPayment::settled()->with('receivedRefundAllocations')->get();

        foreach ($originals as $original) {
            $reserved = round((float) $original->receivedRefundAllocations
                ->whereIn('status', [OrderPaymentRefundAllocationStatus::Pending, OrderPaymentRefundAllocationStatus::Allocated])
                ->sum('allocated_amount'), 2);

            $raw = round((float) $original->amount - $reserved, 2);

            if ($raw < -0.005) {
                $summary['refunds_exceeding_original']++;
                $issues['refunds_exceeding_original'][] = [
                    'original_order_payment_id' => $original->id,
                    'order_id' => $original->order_id,
                    'original_amount' => (float) $original->amount,
                    'reserved' => $reserved,
                ];

                $summary['negative_remaining_balances']++;
                $issues['negative_remaining_balances'][] = [
                    'original_order_payment_id' => $original->id,
                    'order_id' => $original->order_id,
                    'raw_remaining' => $raw,
                ];
            }

            if ($currentFeePercentage > 0 && $original->payment_method === OrderPaymentMethod::Card) {
                $feeConsumed = round((float) $original->receivedRefundAllocations
                    ->where('status', OrderPaymentRefundAllocationStatus::Allocated)
                    ->sum('processing_fee_retained'), 2);

                $cap = round((float) $original->amount * $currentFeePercentage / 100, 2);

                if ($feeConsumed > $cap + 0.01) {
                    $summary['fee_exceeds_lifetime_max']++;
                    $issues['fee_exceeds_lifetime_max'][] = [
                        'original_order_payment_id' => $original->id,
                        'order_id' => $original->order_id,
                        'fee_consumed' => $feeConsumed,
                        'lifetime_cap' => $cap,
                    ];
                }
            }
        }

        $hasFailures = $summary['broken_parent_pointers'] > 0
            || $summary['allocation_total_mismatches'] > 0
            || $summary['split_mismatches'] > 0
            || $summary['refunds_exceeding_original'] > 0
            || $summary['negative_remaining_balances'] > 0
            || $summary['operation_status_disagreements'] > 0
            || $summary['orphan_allocations'] > 0
            || $summary['duplicate_gateway_refund_ids'] > 0
            || $summary['duplicate_idempotency_tokens'] > 0
            || $summary['fee_exceeds_lifetime_max'] > 0
            || $summary['negative_refunded_tax'] > 0;

        return [
            'summary' => $summary,
            'issues' => $issues,
            'has_integrity_failures' => $hasFailures,
        ];
    }
}
