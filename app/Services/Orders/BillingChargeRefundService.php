<?php

namespace App\Services\Orders;

use App\Enums\Billing\BillingChargeType;
use App\Models\Orders\BillingCharge;
use App\Services\ChargeTaxCalculator;

/**
 * Billing Charge Refund Allocation — Safe Linked Refunds.
 *
 * The "who decides" half of this feature — pure decision logic (ownership/
 * eligibility, remaining-refundable, split calculation), no writes, no
 * transaction/locking concerns. RefundStoreController is the "who writes"
 * half (mirrors PaymentAllocationService / RefundPaymentController's
 * established split in this codebase).
 */
class BillingChargeRefundService
{
    /**
     * Server-side ownership/eligibility check. Never trust anything the
     * client claims about a submitted billing_charge_unique_id beyond "a
     * row with this ID exists" — every other fact here is re-derived.
     *
     * "Correct order/account context" is deliberately not checked here:
     * the CRM refund form is customer-scoped only (no order selector
     * exists in this flow), so the customer-ownership check below is the
     * complete applicable context check for this entry point.
     */
    public static function eligibilityError(BillingCharge $charge, int $customerId): ?string
    {
        if ((int) $charge->customer_id !== $customerId) {
            return 'This charge does not belong to the selected customer.';
        }

        if ($charge->billing_charge_type !== BillingChargeType::Fuel && $charge->billing_charge_type !== BillingChargeType::Damage) {
            return 'Only Fuel and Damage charges can be linked to a refund.';
        }

        if (!$charge->isPaid()) {
            $statusLabel = $charge->status?->value ?? 'unknown';
            return "This charge is not currently paid and cannot be refunded (status: {$statusLabel}).";
        }

        if (((float) $charge->amount + (float) $charge->tax_amount) <= 0.0) {
            return 'This charge has no collected value to refund.';
        }

        return null;
    }

    /**
     * Remaining refundable base/tax/total, computed from Allocated
     * (successfully completed) allocations ONLY — per this mission's
     * explicit requirement, Pending and Failed allocations must never
     * consume refundable balance (see BillingChargeRefundStatus's
     * docblock for how this deliberately differs from the order-level
     * allocation system's Pending-reserves-balance design). Never negative.
     *
     * @return array{base: float, tax: float, total: float}
     */
    public static function remainingRefundable(BillingCharge $charge): array
    {
        $consumed = $charge->refunds()->consumed()
            ->selectRaw('COALESCE(SUM(base_amount), 0) as base, COALESCE(SUM(tax_amount), 0) as tax, COALESCE(SUM(total_amount), 0) as total')
            ->first();

        $consumedBase  = (float) ($consumed->base ?? 0);
        $consumedTax   = (float) ($consumed->tax ?? 0);
        $consumedTotal = (float) ($consumed->total ?? 0);

        return [
            'base'  => max(0.0, round((float) $charge->amount - $consumedBase, 2)),
            'tax'   => max(0.0, round((float) $charge->tax_amount - $consumedTax, 2)),
            'total' => max(0.0, round((float) $charge->amount + (float) $charge->tax_amount - $consumedTotal, 2)),
        ];
    }

    /** Integer-cents comparison — immune to binary floating-point noise, same convention PaymentAllocationService::validateAllocationSet() uses. */
    public static function exceedsRemaining(float $requestedAmount, array $remaining): bool
    {
        return (int) round($requestedAmount * 100) > (int) round($remaining['total'] * 100);
    }

    /**
     * Splits a requested refund amount into base/tax at the ORIGINATING
     * CHARGE's own effective rate (tax_amount / amount as recorded when the
     * charge was created) — never today's current global sales_tax rate,
     * which may have changed since. Reuses ChargeTaxCalculator's existing
     * 'reverse' formula (the requested amount is treated as the tax-
     * inclusive total being refunded) rather than inventing a second
     * refund formula.
     *
     * When the requested amount is (within a cent) the entire remaining
     * balance, returns the remaining base/tax EXACTLY rather than
     * re-deriving via division — closes off any rounding residue a final
     * refund could otherwise leave behind after one or more prior partial
     * refunds.
     *
     * @param array{base: float, tax: float, total: float} $remaining
     * @return array{base_amount: float, tax_amount: float, total_amount: float}
     */
    public static function resolveSplit(BillingCharge $charge, float $requestedAmount, array $remaining): array
    {
        if ((int) round($requestedAmount * 100) === (int) round($remaining['total'] * 100)) {
            return [
                'base_amount'  => $remaining['base'],
                'tax_amount'   => $remaining['tax'],
                'total_amount' => round($remaining['base'] + $remaining['tax'], 2),
            ];
        }

        $effectiveRate = (float) $charge->amount > 0
            ? (float) $charge->tax_amount / (float) $charge->amount
            : 0.0;

        $resolved = ChargeTaxCalculator::calculate($requestedAmount, ChargeTaxCalculator::TREATMENT_REVERSE, $effectiveRate);

        return [
            'base_amount'  => $resolved['base_amount'],
            'tax_amount'   => $resolved['tax_amount'],
            'total_amount' => $resolved['total_amount'],
        ];
    }
}
