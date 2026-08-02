<?php

namespace App\Services\Orders;

use App\Enums\Orders\GoodwillReasonCode;
use App\Http\DataObjects\GoodwillCalculation;
use App\Http\DataObjects\GoodwillCalculationFailure;
use App\Http\DataObjects\GoodwillOperationFailure;
use App\Http\DataObjects\HistoricalTaxBasis;
use App\Models\Orders\Order;
use App\Models\Orders\OrderGoodwillAdjustment;
use App\Models\Iam\Personnel\User;
use App\Services\TaxCalculationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Goodwill Adjustment — calculation (FD-002, Truth Table type 17).
 *
 * A Goodwill Adjustment is a manager-authorised, discretionary PRE-TAX
 * reduction of an order's taxable basis, applied so the revised grand total
 * equals the cumulative settled payments accepted as payment in full. It is
 * not tender: it never creates an order_payments row and never appears as a
 * payment method. The money actually received is recorded through the real
 * tender the customer used.
 *
 * MONETARY PRECISION (FD-002). Every value here is integer cents.
 * TaxCalculationService is the ONLY authorised calculation boundary — cents
 * are converted to float dollars solely for that one call and normalised
 * straight back. The reconciliation identity is asserted in INTEGERS:
 *
 *     revisedBasis + nonTaxable + revisedTax + revisedSpecialTax
 *         + addedFees - discount  ===  acceptedAmount
 *
 * If it does not hold exactly, the calculation fails and nothing is written.
 * No parallel tax calculator exists, and no float epsilon is used anywhere.
 *
 * TWO TAXES, NEVER BLENDED (FD-002 Amendment 1 §2). Ordinary sales tax and
 * special tax are levied at different rates on the same basis. Both are
 * recomputed on the reduced basis, each at its OWN rate. The combined rate is
 * used only to solve the inclusive→exclusive extraction in one call; the two
 * taxes are then derived separately and stored separately.
 *
 * preview() and apply() share this one calculation path, so the figures an
 * approving manager sees cannot differ from the figures committed.
 */
class GoodwillAdjustmentService
{
    /**
     * Compute the adjustment implied by accepting `$acceptedCents` as payment
     * in full. Read-only: touches nothing, writes nothing.
     *
     * @param  int  $acceptedCents  Cumulative settled payments AFTER the payment
     *         being recorded now — never the newest payment alone (FD-002 §2).
     */
    public static function calculate(Order $order, int $acceptedCents): GoodwillCalculation
    {
        $basis = HistoricalTaxBasisResolver::resolve($order);

        if (! $basis->succeeded()) {
            return GoodwillCalculation::failed(
                GoodwillCalculationFailure::BasisUnreconstructable,
                $basis->failure,
            );
        }

        if ($acceptedCents <= 0) {
            // A $0.00 concession is a write-off, not Goodwill. Truth Table
            // §5.9 leaves that undecided, so it is refused rather than
            // silently treated as a 100% adjustment.
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NoPaymentReceived);
        }

        $originalBasisCents      = $basis->ordinaryBasisCents;
        $originalUntaxedCents    = $basis->untaxedMerchandiseBasisCents;
        $originalTaxCents        = $basis->ordinaryTaxCents;
        $originalSpecialTaxCents = $basis->specialTaxCents;
        $protectedCents          = $basis->protectedCents();
        $discountCents           = $basis->discountCents;

        // Everything Goodwill may reduce: taxable AND untaxed merchandise.
        // Tax-exempt merchandise is still merchandise (FD-002 Amendment 3).
        $merchandiseCents = $originalBasisCents + $originalUntaxedCents;

        $originalGrandTotalCents = $merchandiseCents
            + $originalTaxCents
            + $originalSpecialTaxCents
            + $protectedCents
            - $discountCents;

        if ($acceptedCents >= $originalGrandTotalCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        if ($merchandiseCents <= 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        // Protected components and the discount sit outside the reduction.
        $absorbableCents = $acceptedCents - $protectedCents + $discountCents;

        if ($absorbableCents < 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::PaymentBelowNonReducibleFloor);
        }

        $ordinaryRate = $basis->ordinaryRate();
        $specialRate  = $basis->specialRate();

        // Reducing merchandise proportionally keeps each bucket's share of the
        // basis constant, so the tax the revised merchandise attracts is the
        // basis times a blended rate weighted by how much of the merchandise
        // was taxable at all. For a fully tax-exempt order both weights are
        // zero, the blended rate is zero, and the revised merchandise simply
        // equals the accepted amount — which is exactly the intended
        // $200 -> $185 with $15 waived and no tax anywhere.
        $effectiveRate = ($originalBasisCents / $merchandiseCents) * $ordinaryRate
            + ($basis->specialBasisCents / $merchandiseCents) * $specialRate;

        // ONE call to the authorised boundary.
        $breakdown = TaxCalculationService::extractTaxFromInclusiveAmount(
            $absorbableCents / 100,
            $effectiveRate,
        );

        $revisedMerchandiseCents = (int) round($breakdown->baseAmount * 100);

        if ($revisedMerchandiseCents < 0 || $revisedMerchandiseCents > $merchandiseCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        // Split the revised merchandise back into its two buckets in the same
        // proportion they held before, with the residual cent to the taxable
        // side so the identity closes.
        $revisedBasisCents   = $originalBasisCents > 0
            ? (int) round($revisedMerchandiseCents * $originalBasisCents / $merchandiseCents)
            : 0;
        $revisedUntaxedCents = $revisedMerchandiseCents - $revisedBasisCents;

        // Total tax is the remainder by construction; special tax is derived at
        // its own rate and the residual cent falls to ordinary tax. The two are
        // never reported as one blended figure.
        $totalRevisedTaxCents   = $absorbableCents - $revisedMerchandiseCents;
        $revisedSpecialBasis    = $merchandiseCents > 0
            ? (int) round($revisedMerchandiseCents * $basis->specialBasisCents / $merchandiseCents)
            : 0;
        $revisedSpecialTaxCents = $specialRate > 0 ? (int) round($revisedSpecialBasis * $specialRate) : 0;
        $revisedTaxCents        = $totalRevisedTaxCents - $revisedSpecialTaxCents;

        if ($revisedTaxCents < 0 || $revisedSpecialTaxCents < 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        $goodwillCents = $merchandiseCents - $revisedMerchandiseCents;

        if ($goodwillCents <= 0) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::NothingToWaive);
        }

        // ── The identity, asserted in integers ──────────────────────────────
        $reconciled = $revisedBasisCents
            + $revisedUntaxedCents
            + $revisedTaxCents
            + $revisedSpecialTaxCents
            + $protectedCents
            - $discountCents;

        if ($reconciled !== $acceptedCents) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::ReconciliationFailed);
        }

        $allocations = self::allocate($basis, $goodwillCents, $ordinaryRate, $revisedTaxCents, $revisedSpecialTaxCents);

        if ($allocations === null) {
            return GoodwillCalculation::failed(GoodwillCalculationFailure::AllocationImbalance);
        }

        return GoodwillCalculation::resolved(
            originalBasisCents:              $originalBasisCents,
            originalUntaxedMerchandiseCents: $originalUntaxedCents,
            originalTaxCents:        $originalTaxCents,
            originalSpecialTaxCents: $originalSpecialTaxCents,
            originalGrandTotalCents: $originalGrandTotalCents,
            revisedBasisCents:               $revisedBasisCents,
            revisedUntaxedMerchandiseCents:  $revisedUntaxedCents,
            revisedTaxCents:         $revisedTaxCents,
            revisedSpecialTaxCents:  $revisedSpecialTaxCents,
            revisedGrandTotalCents:  $acceptedCents,
            goodwillCents:           $goodwillCents,
            acceptedCents:           $acceptedCents,
            protectedCents:          $protectedCents,
            protectedLineCents:      $basis->protectedLineCents,
            discountCents:           $discountCents,
            lineAllocations:         $allocations,
            basisSnapshot:           [
                'source'                => $basis->source?->value,
                'ordinary_basis_cents'  => $originalBasisCents,
                'ordinary_tax_cents'    => $originalTaxCents,
                'special_basis_cents'   => $basis->specialBasisCents,
                'special_tax_cents'     => $originalSpecialTaxCents,
                'untaxed_merchandise_cents' => $originalUntaxedCents,
                'protected_cents'       => $protectedCents,
                'discount_cents'        => $discountCents,
            ],
        );
    }

    /**
     * Spread the waived amount across eligible taxable lines.
     *
     * Proportional by the line's own basis, with leftover cents distributed by
     * largest fractional remainder and ties broken by ascending id — so the
     * same order always produces the same allocation, which is what makes an
     * exact reversal possible. Returns null if the shares fail to sum to the
     * waived total, which the caller treats as a hard failure rather than
     * absorbing the difference somewhere.
     *
     * Reduces `sub_total`, NOT `price`: ProductSalesPerformanceEngine reads
     * `order_products.sub_total` as its revenue source, and Goodwill is an
     * order-level concession rather than a repricing of the item.
     *
     * An order with no lines (a resolved extension child) allocates nothing —
     * there is no line to attribute the reduction to, and the order-level
     * totals carry it instead.
     *
     * @return array<int, array<string,int>>|null
     */
    private static function allocate(
        HistoricalTaxBasis $basis,
        int $goodwillCents,
        float $ordinaryRate,
        int $revisedTaxCents,
        int $revisedSpecialTaxCents = 0,
    ): ?array {
        // ALL reducible merchandise lines share the reduction — taxable and
        // untaxed alike. Untaxed merchandise is still merchandise (FD-002
        // Amendment 3); only protected fee-type lines are excluded.
        $eligible = array_values(array_filter(
            $basis->lines,
            fn (array $l) => ($l['reducible'] ?? true),
        ));

        if ($eligible === []) {
            return [];
        }

        $totalBasis = array_sum(array_column($eligible, 'basis_cents'));

        if ($totalBasis <= 0) {
            return null;
        }

        $shares     = [];
        $remainders = [];
        $assigned   = 0;

        foreach ($eligible as $i => $line) {
            $exact             = $goodwillCents * $line['basis_cents'] / $totalBasis;
            $shares[$i]        = (int) floor($exact);
            $remainders[$i]    = $exact - $shares[$i];
            $assigned         += $shares[$i];
        }

        // Largest remainder first; ties fall to the lower line id for determinism.
        $order = array_keys($remainders);
        usort($order, function ($a, $b) use ($remainders, $eligible) {
            if ($remainders[$a] === $remainders[$b]) {
                return $eligible[$a]['id'] <=> $eligible[$b]['id'];
            }

            return $remainders[$b] <=> $remainders[$a];
        });

        $leftover = $goodwillCents - $assigned;
        foreach ($order as $i) {
            if ($leftover <= 0) {
                break;
            }
            $shares[$i]++;
            $leftover--;
        }

        if (array_sum($shares) !== $goodwillCents) {
            return null;
        }

        // Recompute tax only on lines that actually carried ordinary tax; an
        // untaxed merchandise line stays untaxed however much it is reduced.
        // The residual cent lands on the last TAXED line, mirroring
        // SalesTaxReportEngine's remainder-to-last-row convention rather than
        // inventing a second one.
        $allocations    = [];
        $taxAssigned    = 0;
        $taxedIndexes   = array_keys(array_filter($eligible, fn (array $l) => $l['taxable']));
        $lastTaxedIndex = $taxedIndexes === [] ? null : end($taxedIndexes);

        // Special tax follows the same shape on its own set of lines: only
        // lines that actually carried it can carry it afterwards, and the
        // residual cent lands on the last such line. Ordinary and special tax
        // are allocated independently and never blended into one figure.
        $specialAssigned      = 0;
        $specialBasisTotal    = array_sum(array_map(
            fn (array $l) => ($l['special_tax_cents'] ?? 0) > 0 ? $l['basis_cents'] : 0,
            $eligible,
        ));
        $specialIndexes       = array_keys(array_filter($eligible, fn (array $l) => ($l['special_tax_cents'] ?? 0) > 0));
        $lastSpecialIndex     = $specialIndexes === [] ? null : end($specialIndexes);

        foreach ($eligible as $i => $line) {
            $basisAfter = $line['basis_cents'] - $shares[$i];

            if ($basisAfter < 0) {
                return null;
            }

            if (! $line['taxable']) {
                $taxAfter = 0;
            } elseif ($i === $lastTaxedIndex) {
                $taxAfter = $revisedTaxCents - $taxAssigned;
            } else {
                $taxAfter = (int) round($basisAfter * $ordinaryRate);
                $taxAssigned += $taxAfter;
            }

            if (($line['special_tax_cents'] ?? 0) <= 0) {
                $specialAfter = 0;
            } elseif ($i === $lastSpecialIndex) {
                $specialAfter = $revisedSpecialTaxCents - $specialAssigned;
            } else {
                $specialAfter = $specialBasisTotal > 0
                    ? (int) round($revisedSpecialTaxCents * $line['basis_cents'] / $specialBasisTotal)
                    : 0;
                $specialAssigned += $specialAfter;
            }

            if ($taxAfter < 0 || $specialAfter < 0) {
                return null;
            }

            $allocations[] = [
                'line_id'      => $line['id'],
                'basis_before' => $line['basis_cents'],
                'basis_after'  => $basisAfter,
                'tax_before'   => $line['tax_cents'],
                'tax_after'    => $taxAfter,
                'special_tax_before' => $line['special_tax_cents'] ?? 0,
                'special_tax_after'  => $specialAfter,
                // Protected: recorded so a reversal can prove it was never
                // touched, never so it can be changed.
                'added_fees'   => $line['added_fees_cents'] ?? 0,
                'reduced_by'   => $shares[$i],
            ];
        }

        if (array_sum(array_column($allocations, 'tax_after')) !== $revisedTaxCents) {
            return null;
        }

        if (array_sum(array_column($allocations, 'special_tax_after')) !== $revisedSpecialTaxCents) {
            return null;
        }

        return $allocations;
    }

    // ══ Writer ═════════════════════════════════════════════════════════════

    /**
     * Apply a Goodwill Adjustment atomically.
     *
     * TRANSACTION BOUNDARY. Everything below happens inside ONE
     * DB::transaction. The order row is locked FOR UPDATE before anything is
     * read that a concurrent payment could change, and it stays locked until
     * commit — so a payment landing mid-operation cannot make the accepted
     * amount stale between the check and the write. Any failure returns a
     * named refusal and the transaction rolls back, leaving neither an
     * adjustment nor a mutated order.
     *
     * Goodwill NEVER creates a payment row. `$orderPaymentId` merely links the
     * real tender that was recorded alongside, by whatever normal payment path
     * recorded it.
     *
     * @param  int  $expectedAcceptedCents  What the caller believes cumulative
     *         settled payments are. Re-read under the lock and rejected as
     *         stale if it disagrees — the browser's number is never trusted.
     * @return array{ok:bool, failure:?GoodwillOperationFailure, calculation:?GoodwillCalculation, adjustment:?OrderGoodwillAdjustment, replayed:bool}
     */
    public static function apply(
        Order $order,
        int $expectedAcceptedCents,
        GoodwillReasonCode $reason,
        ?string $reasonNote,
        User $approvedBy,
        User $performedBy,
        ?string $idempotencyToken = null,
        ?int $orderPaymentId = null,
    ): array {
        return DB::transaction(function () use (
            $order, $expectedAcceptedCents, $reason, $reasonNote,
            $approvedBy, $performedBy, $idempotencyToken, $orderPaymentId
        ) {
            // Idempotency first: a retry must never produce a second
            // adjustment. The unique index is the backstop; this is the
            // graceful path that returns the original result.
            if ($idempotencyToken !== null) {
                $existing = OrderGoodwillAdjustment::where('idempotency_token', $idempotencyToken)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return self::ok(null, $existing, replayed: true);
                }
            }

            /** @var Order|null $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                return self::fail(GoodwillOperationFailure::AdjustmentNotFound);
            }

            if (! self::authorises($approvedBy, 'goodwill.apply')) {
                return self::fail(GoodwillOperationFailure::PermissionDenied);
            }

            if ($reason->requiresNote() && trim((string) $reasonNote) === '') {
                return self::fail(GoodwillOperationFailure::ReasonNoteRequired);
            }

            // One active adjustment per order, enforced under the lock. MySQL
            // has no partial unique index, so the lock IS the enforcement.
            $active = OrderGoodwillAdjustment::where('order_id', $locked->id)
                ->whereNull('reversed_at')
                ->lockForUpdate()
                ->exists();

            if ($active) {
                return self::fail(GoodwillOperationFailure::ActiveAdjustmentExists);
            }

            // Durable downstream artifacts — checked BEFORE any mutation and
            // under the order lock, so an invoice or ledger posting cannot be
            // created between the check and the write. Goodwill never edits a
            // running account balance, an invoice total, or an invoice item;
            // it refuses and defers to a workflow that amends them explicitly.
            if ($blocker = self::applyBlocker($locked)) {
                return self::fail($blocker);
            }

            // Cumulative settled payments, re-read under the lock.
            $settledCents = (int) round(((float) $locked->total_paid) * 100);

            if ($settledCents !== $expectedAcceptedCents) {
                return self::fail(GoodwillOperationFailure::StaleOrderState);
            }

            $calc = self::calculate($locked, $settledCents);

            if (! $calc->succeeded()) {
                return self::fail(GoodwillOperationFailure::CalculationFailed, $calc);
            }

            // Protected fee-type lines cannot occur today; the write path for
            // them is therefore untested, so it is refused rather than guessed.
            if ($calc->protectedLineCents !== 0) {
                return self::fail(GoodwillOperationFailure::ProtectedLineUnsupported, $calc);
            }

            $statusBefore = $locked->is_paid ? 'Paid' : 'Partial';

            $adjustment = OrderGoodwillAdjustment::create([
                'unique_id'            => (string) Str::uuid(),
                'order_id'             => $locked->id,
                'goodwill_amount'      => $calc->goodwillCents / 100,
                'reason_code'          => $reason->value,
                'reason_note'          => $reasonNote,
                'approved_by'          => $approvedBy->id,
                'performed_by'         => $performedBy->id,
                'original_subtotal'    => $calc->originalMerchandiseCents() / 100,
                'original_tax'         => $calc->originalTaxCents / 100,
                'original_special_tax' => $calc->originalSpecialTaxCents / 100,
                'original_grand_total' => $calc->originalGrandTotalCents / 100,
                'revised_subtotal'     => $calc->revisedMerchandiseCents() / 100,
                'revised_tax'          => $calc->revisedTaxCents / 100,
                'revised_special_tax'  => $calc->revisedSpecialTaxCents / 100,
                'revised_grand_total'  => $calc->revisedGrandTotalCents / 100,
                'line_allocations'     => $calc->lineAllocations,
                'basis_snapshot'       => $calc->basisSnapshot,
                'payments_accepted'    => $settledCents / 100,
                'payment_status_before' => $statusBefore,
                'payment_status_after'  => 'Paid',
                'order_payment_id'     => $orderPaymentId,
                'idempotency_token'    => $idempotencyToken,
            ]);

            // Canonical order totals. Special tax moves with the basis that
            // generated it; added fees are protected and never move.
            $locked->subtotal           = $calc->revisedMerchandiseCents() / 100;
            $locked->tax_amount         = $calc->revisedTaxCents / 100;
            $locked->special_tax_amount = $calc->revisedSpecialTaxCents / 100;
            $locked->grand_total        = $calc->revisedGrandTotalCents / 100;
            $locked->save();

            // Allocated line reductions — sub_total only; `price` is the
            // original unit price and is never repriced by a concession, and
            // `product_data` is the immutable original snapshot.
            foreach ($calc->lineAllocations as $row) {
                DB::table('order_products')
                    ->where('id', $row['line_id'])
                    ->update([
                        'sub_total'   => $row['basis_after'] / 100,
                        'tax'         => $row['tax_after'] / 100,
                        'special_tax' => ($row['special_tax_after'] ?? 0) / 100,
                        // Same composition CartHelper::buildCartItem() uses:
                        // basis + ordinary tax + special tax + added fees.
                        'total'       => ($row['basis_after'] + $row['tax_after']
                            + ($row['special_tax_after'] ?? 0) + ($row['added_fees'] ?? 0)) / 100,
                        'updated_at'  => now(),
                    ]);
            }

            // Paid status is never assigned directly — it is whatever the
            // canonical accessor now derives from the revised totals.
            $locked->refresh();

            return self::ok($calc, $adjustment->fresh(), replayed: false);
        });
    }

    /**
     * Reverse an adjustment, restoring the pre-adjustment financial state.
     *
     * TRANSACTION BOUNDARY. One DB::transaction; the order and the adjustment
     * row are both locked FOR UPDATE before any check, so a concurrent
     * reversal or refund cannot interleave. Restoration reads ONLY the stored
     * snapshot — never current product data, which may since have changed for
     * unrelated reasons.
     *
     * The customer's real payments are never touched. Reopening the balance is
     * a consequence of restoring grand_total, not a separate write.
     *
     * @return array{ok:bool, failure:?GoodwillOperationFailure, calculation:?GoodwillCalculation, adjustment:?OrderGoodwillAdjustment, replayed:bool}
     */
    public static function reverse(
        Order $order,
        User $reversedBy,
        string $reversalReason,
    ): array {
        return DB::transaction(function () use ($order, $reversedBy, $reversalReason) {
            /** @var Order|null $locked */
            $locked = Order::whereKey($order->id)->lockForUpdate()->first();

            if (! $locked) {
                return self::fail(GoodwillOperationFailure::AdjustmentNotFound);
            }

            if (! self::authorises($reversedBy, 'goodwill.reverse')) {
                return self::fail(GoodwillOperationFailure::PermissionDenied);
            }

            /** @var OrderGoodwillAdjustment|null $adjustment */
            $adjustment = OrderGoodwillAdjustment::where('order_id', $locked->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $adjustment) {
                return self::fail(GoodwillOperationFailure::AdjustmentNotFound);
            }

            if ($adjustment->isReversed()) {
                return self::fail(GoodwillOperationFailure::AlreadyReversed);
            }

            // Durable artifacts created SINCE the adjustment make restoration
            // unsafe: each one states the adjusted totals, and restoring the
            // originals would silently contradict it. Checked under the lock,
            // before anything is written.
            if ($blocker = self::reversalBlocker($locked, $adjustment)) {
                return self::fail($blocker);
            }

            // Restore from the SNAPSHOT, never from current product data.
            $locked->subtotal           = $adjustment->original_subtotal;
            $locked->tax_amount         = $adjustment->original_tax;
            $locked->special_tax_amount = $adjustment->original_special_tax;
            $locked->grand_total        = $adjustment->original_grand_total;
            $locked->save();

            foreach (($adjustment->line_allocations ?? []) as $row) {
                DB::table('order_products')
                    ->where('id', $row['line_id'])
                    ->update([
                        'sub_total'   => $row['basis_before'] / 100,
                        'tax'         => $row['tax_before'] / 100,
                        'special_tax' => ($row['special_tax_before'] ?? 0) / 100,
                        'total'       => ($row['basis_before'] + $row['tax_before']
                            + ($row['special_tax_before'] ?? 0) + ($row['added_fees'] ?? 0)) / 100,
                        'updated_at'  => now(),
                    ]);
            }

            $adjustment->reversed_at     = now();
            $adjustment->reversed_by     = $reversedBy->id;
            $adjustment->reversal_reason = $reversalReason;
            $adjustment->save();

            $locked->refresh();

            return self::ok(null, $adjustment->fresh(), replayed: false);
        });
    }

    // ══ Durable downstream artifacts ═══════════════════════════════════════

    /**
     * Has this order been posted to the customer's account?
     *
     * `customer_accounts` rows of type `order` snapshot `order_products.
     * sub_total` and `order_products.tax` — precisely the two columns a
     * Goodwill Adjustment mutates — and `LedgerBalanceService::
     * applyTransaction()` then computes a running `balance` that every later
     * row inherits. Reducing the order without touching that chain would leave
     * the customer's statement still claiming the full pre-adjustment
     * receivable.
     *
     * ANY type is disqualifying, not just `order`: a payment, charge, or
     * discount row against this order means the order is represented in the
     * ledger, and the running balance is a single chain regardless of which
     * row type started it. Soft-deleted rows are excluded — a deleted entry no
     * longer participates in the balance.
     */
    private static function hasAccountsReceivable(Order $order): bool
    {
        return DB::table('customer_accounts')
            ->where('order_id', $order->id)
            ->whereNull('deleted_at')
            ->exists();
    }

    /**
     * Is this order bound to an invoice?
     *
     * Checked three ways because the binding is written in three places by
     * `Crm\Customers\Invoice\StoreController`: the order is stamped with
     * `invoice_id`, the matching `customer_accounts` row is bound via
     * `invoice_item_id`, and the `invoice_items` row itself references the
     * ORDER PRODUCT's `unique_id` in `item_id` with `type = 'order'`. Any one
     * of them means an external document states this order's totals.
     *
     * The third check is not redundant paranoia: `StoreController` only stamps
     * `orders.invoice_id` when it finds an unbound `customer_accounts` row, so
     * an invoice can reference an order product while the order column stays
     * null.
     */
    private static function hasInvoice(Order $order): bool
    {
        if ($order->invoice_id !== null) {
            return true;
        }

        if (DB::table('customer_accounts')
            ->where('order_id', $order->id)
            ->whereNotNull('invoice_id')
            ->whereNull('deleted_at')
            ->exists()) {
            return true;
        }

        $lineUniqueIds = DB::table('order_products')
            ->where('order_id', $order->id)
            ->pluck('unique_id')
            ->all();

        if ($lineUniqueIds === []) {
            return false;
        }

        return DB::table('invoice_items')
            ->where('type', 'order')
            ->whereIn('item_id', $lineUniqueIds)
            ->exists();
    }

    /**
     * Has a receipt already been created for this order?
     *
     * `ReceiptService::getOrCreateReceipt()` freezes `subtotal`/`sales_tax`/
     * `total` and per-item `tax`/`total`, and thereafter RETURNS THE EXISTING
     * ROW without ever refreshing it. A receipt created before an adjustment
     * would therefore stay current and stale forever.
     *
     * TEMPORARY — SCHEDULED FOR REMOVAL IN COMMIT 4B (receipt supersession
     * and reissue). It exists only because shipping an apply path that
     * knowingly leaves an existing receipt permanently current and stale is
     * not acceptable. Once supersession is atomic with the adjustment, the
     * correct behaviour becomes supersede-and-reissue and this refusal must be
     * deleted from {@see self::applyBlocker()} — NOT from
     * {@see self::reversalBlocker()}, where a receipt created after the
     * adjustment remains a genuine blocker.
     *
     * Both the row and the order's own `receipt_status` flag are checked,
     * since a legacy order could carry the flag without a surviving row.
     */
    private static function hasReceipt(Order $order): bool
    {
        if ((string) $order->receipt_status === 'created') {
            return true;
        }

        return DB::table('receipts')->where('order_id', $order->id)->exists();
    }

    /**
     * What, if anything, forbids applying Goodwill to this order?
     *
     * Ordered most-consequential first so the operator is told about the
     * accounting record before the paperwork. Every check is an EXPLICIT
     * RELATIONSHIP — a foreign key or a bound identifier — never a timestamp
     * heuristic.
     */
    private static function applyBlocker(Order $order): ?GoodwillOperationFailure
    {
        if (self::hasAccountsReceivable($order)) {
            return GoodwillOperationFailure::AccountsReceivableAlreadyPosted;
        }

        if (self::hasInvoice($order)) {
            return GoodwillOperationFailure::InvoiceAlreadyIssued;
        }

        // TEMPORARY — remove in Commit 4B, once receipt supersession is atomic
        // with the adjustment. See hasReceipt()'s docblock.
        if (self::hasReceipt($order)) {
            return GoodwillOperationFailure::ReceiptAlreadyIssued;
        }

        return null;
    }

    /**
     * What, if anything, forbids reversing this adjustment?
     *
     * The relationship checks come first and the timestamp is only supporting
     * evidence, deliberately. An order that already had AR, an invoice, or a
     * receipt could never have received Goodwill in the first place —
     * {@see self::applyBlocker()} refuses it — so the mere PRESENCE of one of
     * these artifacts at reversal time is itself proof that it arrived after
     * the adjustment. That is a stronger and simpler test than comparing
     * timestamps, which can tie, skew, or be back-dated.
     *
     * Refunds are different: they are legitimately possible on an adjusted
     * order, so there the timestamp comparison is load-bearing and is kept.
     */
    private static function reversalBlocker(Order $order, OrderGoodwillAdjustment $adjustment): ?GoodwillOperationFailure
    {
        if (self::hasAccountsReceivable($order)) {
            return GoodwillOperationFailure::ReversalBlockedByAccountsReceivable;
        }

        if (self::hasInvoice($order)) {
            return GoodwillOperationFailure::ReversalBlockedByInvoice;
        }

        if (self::hasReceipt($order)) {
            return GoodwillOperationFailure::ReversalBlockedByReceipt;
        }

        // A refund sized against a basis that would no longer exist.
        $laterRefund = $order->payments()
            ->whereIn('status', ['Refunded', 'Partial Refund'])
            ->where('created_at', '>=', $adjustment->created_at)
            ->exists();

        return $laterRefund ? GoodwillOperationFailure::ReversalUnsafe : null;
    }

    /**
     * Does this user genuinely hold the permission?
     *
     * Checked through Spatie DIRECTLY rather than through `$user->can()`,
     * deliberately. `AppServiceProvider::gatesRegistration()` registers
     * `Gate::before(fn () => true)` as a documented small-business posture —
     * every signed-in user passes every ability check application-wide — so
     * `can()` would return true for anyone and this guard would be decorative.
     *
     * FD-002 §7.3 requires that authority to reduce revenue is NOT implied by
     * authority to receive a payment, and requires the server to enforce it
     * rather than merely hide the UI. Consulting the permission store directly
     * is the only way to honour that while leaving the application-wide Gate
     * posture exactly as the business chose it. Removing that global bypass is
     * a separate decision with a far wider blast radius than this feature.
     */
    private static function authorises(User $user, string $permission): bool
    {
        if (! method_exists($user, 'hasPermissionTo')) {
            return false;
        }

        try {
            return $user->hasPermissionTo($permission);
        } catch (\Throwable) {
            // An undefined permission means nobody holds it.
            return false;
        }
    }

    /** @return array{ok:bool, failure:?GoodwillOperationFailure, calculation:?GoodwillCalculation, adjustment:?OrderGoodwillAdjustment, replayed:bool} */
    private static function ok(?GoodwillCalculation $calc, ?OrderGoodwillAdjustment $adjustment, bool $replayed): array
    {
        return ['ok' => true, 'failure' => null, 'calculation' => $calc, 'adjustment' => $adjustment, 'replayed' => $replayed];
    }

    /** @return array{ok:bool, failure:?GoodwillOperationFailure, calculation:?GoodwillCalculation, adjustment:?OrderGoodwillAdjustment, replayed:bool} */
    private static function fail(GoodwillOperationFailure $failure, ?GoodwillCalculation $calc = null): array
    {
        return ['ok' => false, 'failure' => $failure, 'calculation' => $calc, 'adjustment' => null, 'replayed' => false];
    }
}
