<?php

namespace App\Services\Goodwill;

use App\Services\Discounts\Targets\OrderDiscountTarget;

/**
 * How large must the concession be for the order to be settled by what was
 * actually collected?
 *
 * ── WHY THIS IS NOT SUBTRACTION ───────────────────────────────────────────
 *
 * The obvious answer — the remaining balance — is wrong. A pre-tax concession
 * reduces the merchandise basis, and both ordinary and special tax are derived
 * from that basis, so they fall with it. Granting $50 off a taxable order
 * reduces the grand total by rather more than $50. Sizing the concession by the
 * balance would overshoot and leave the customer in credit.
 *
 * ── WHY A SEARCH RATHER THAN A FORMULA ────────────────────────────────────
 *
 * The relationship is not linear, because each tax component is rounded to the
 * cent. As the concession grows by one cent the grand total falls by one, two
 * or three, depending on where the rounding boundaries land. A closed-form
 * inverse would be right most of the time and quietly wrong at the boundaries —
 * which is where money goes missing.
 *
 * `grand(a)` is monotonically non-increasing in the concession `a`, so a binary
 * search finds the smallest concession whose revised total does not exceed what
 * was collected, in around twenty-five evaluations, exactly. Each evaluation is
 * `OrderDiscountTarget::previewTotals()` — the shared engine's own arithmetic,
 * the same function the writer persists from. This class computes no total
 * itself; it only asks the engine questions and compares the answers.
 *
 * ── THE RESIDUAL ──────────────────────────────────────────────────────────
 *
 * Because the total steps by more than a cent at a rounding boundary, the
 * closest reachable total is sometimes a cent or two below what was collected.
 * That remainder is returned explicitly, never folded into the concession. The
 * operational rule is asymmetric and deliberately tighter than the storage
 * bound:
 *
 *     0.00 <= residual <= 0.02
 *
 * Non-negative because the chosen concession never takes the revised total
 * above what was collected — the customer is never left owing a cent that the
 * order claims is settled. The model's symmetric ±$0.02 check remains the
 * broader storage safeguard behind it, not this rule.
 */
final class GoodwillConcessionSolver
{
    public const MAX_RESIDUAL_CENTS = 2;

    /**
     * @param  int  $acceptedPaymentCents  settled payments, read live under the caller's lock
     *
     * @throws GoodwillException when no concession can close the order within tolerance
     */
    public static function solve(OrderDiscountTarget $target, int $acceptedPaymentCents): GoodwillSolution
    {
        // The engine's view at the CURRENT concession. Taking the bounds from
        // here rather than from `eligibleProductValue()` keeps everything in
        // integer cents from one source, so no float conversion can put a
        // candidate a cent past what the writer would accept.
        $before = $target->previewTotals(0.0);

        $maxAdditionalCents = $before['subtotal'] - $before['discount'];

        if ($maxAdditionalCents <= 0) {
            throw GoodwillException::because(GoodwillFailure::NoMerchandiseLines);
        }

        if ($before['grand_total'] <= $acceptedPaymentCents) {
            throw GoodwillException::because(GoodwillFailure::AlreadyPaidInFull);
        }

        // Even zeroing the merchandise entirely leaves fees and other
        // non-merchandise charges standing. If that floor is still above what
        // was collected, no merchandise concession can close the order.
        $floor = self::grandTotalAt($target, $maxAdditionalCents);

        if ($floor > $acceptedPaymentCents) {
            throw GoodwillException::because(
                GoodwillFailure::Unreachable,
                'The lowest reachable total is '.number_format($floor / 100, 2)
                .' against '.number_format($acceptedPaymentCents / 100, 2).' collected.'
            );
        }

        // Smallest concession whose revised total does not exceed what was
        // collected. Monotonic by construction, so the invariant holds
        // throughout: grand(lo-1) > accepted >= grand(hi).
        $lo = 1;
        $hi = $maxAdditionalCents;

        while ($lo < $hi) {
            $mid = intdiv($lo + $hi, 2);

            if (self::grandTotalAt($target, $mid) <= $acceptedPaymentCents) {
                $hi = $mid;
            } else {
                $lo = $mid + 1;
            }
        }

        $totals = $target->previewTotals($lo / 100);
        $residual = $acceptedPaymentCents - $totals['grand_total'];

        // Defensive: the search guarantees a non-negative residual, and the
        // step size guarantees it is under three cents. Both are asserted
        // rather than assumed, because a violation would mean the engine's
        // arithmetic is not what this search believes it to be — and that must
        // stop the operation, not be written to the ledger.
        if ($residual < 0 || $residual > self::MAX_RESIDUAL_CENTS) {
            throw GoodwillException::because(
                GoodwillFailure::ResidualOutOfBounds,
                'Closest reachable total leaves '.number_format($residual / 100, 2).'.'
            );
        }

        return new GoodwillSolution(
            concessionCents: $lo,
            acceptedPaymentCents: $acceptedPaymentCents,
            residualCents: $residual,
            totals: $totals,
            totalsBefore: $before,
        );
    }

    private static function grandTotalAt(OrderDiscountTarget $target, int $additionalCents): int
    {
        return $target->previewTotals($additionalCents / 100)['grand_total'];
    }
}
