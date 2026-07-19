<?php

namespace App\Services;

use InvalidArgumentException;

/**
 * Sales Tax Architecture Correction — the single canonical implementation of
 * the three Sales Tax Treatment choices already exposed in the CRM "New
 * Charge" modal (Add Sales Tax / Tax Free / Reverse Sales Tax). Every
 * fuel/damage/extension charge creation path must call this instead of
 * re-deriving the formula inline — before this class existed, the same
 * three formulas were duplicated (correctly) in AlertChargeController and
 * FuelChargeStoreController, duplicated (via CustomHelper::updateCreditBalance())
 * for the CustomerAccount ledger's own balance math, and simply ABSENT in
 * DamageChargeStoreController and the CRM ChargeStoreController — the exact
 * defect that let the CRM "New Charge" modal show a correct live tax
 * preview while silently persisting zero tax on the BillingCharge row.
 *
 * Formulas here are reproduced byte-for-byte from the already-working
 * AlertChargeController/FuelChargeStoreController implementation — this is
 * an extraction, not a new formula:
 *   Add:     base = entered,                      tax = round(entered * rate, 2)
 *   Free:    base = entered,                      tax = 0
 *   Reverse: base = round(entered / (1+rate), 2),  tax = round(entered - base, 2)
 * (Reverse mirrors PaymentAllocationService::proportionalTaxRefund() /
 * CustomHelper::calculateRefundSalesTax()'s tax-inclusive back-out — the
 * same convention already used everywhere a "this total already includes
 * tax" figure needs splitting.)
 */
class ChargeTaxCalculator
{
    public const TREATMENT_ADD = 'add';
    public const TREATMENT_FREE = 'free';
    public const TREATMENT_REVERSE = 'reverse';

    public const TREATMENTS = [self::TREATMENT_ADD, self::TREATMENT_FREE, self::TREATMENT_REVERSE];

    /**
     * @return array{treatment: string, entered_amount: float, base_amount: float, tax_amount: float, total_amount: float, tax_rate: float}
     */
    public static function calculate(float $enteredAmount, string $treatment, float $rate): array
    {
        if (!in_array($treatment, self::TREATMENTS, true)) {
            throw new InvalidArgumentException("Invalid sales tax treatment '{$treatment}' — must be one of: " . implode(', ', self::TREATMENTS));
        }

        $rate = max(0.0, $rate);

        [$base, $tax] = match ($treatment) {
            self::TREATMENT_ADD => [
                round($enteredAmount, 2),
                round($enteredAmount * $rate, 2),
            ],
            self::TREATMENT_REVERSE => (function () use ($enteredAmount, $rate) {
                $divisor = $rate > 0 ? (1 + $rate) : 1;
                $base = round($enteredAmount / $divisor, 2);
                return [$base, round($enteredAmount - $base, 2)];
            })(),
            self::TREATMENT_FREE => [round($enteredAmount, 2), 0.0],
        };

        return [
            'treatment'      => $treatment,
            'entered_amount' => round($enteredAmount, 2),
            'base_amount'    => $base,
            'tax_amount'     => $tax,
            'total_amount'   => round($base + $tax, 2),
            'tax_rate'       => $rate,
        ];
    }

    /** Current global sales_tax rate (decimal fraction, e.g. 0.0975) — same Setting row the canonical checkout pipeline and rental extensions already read. */
    public static function currentRate(): float
    {
        return (float) (\App\Helpers\ConfigurationHelper::getSettings(null, 'sales_tax') ?? 0);
    }

    public static function isValidTreatment(?string $treatment): bool
    {
        return $treatment !== null && in_array($treatment, self::TREATMENTS, true);
    }
}
