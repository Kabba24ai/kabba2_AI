<?php

namespace App\Helpers;

/**
 * Smart rental price rounding.
 *
 * Derived rental prices (Weekend Special, Weekly, Monthly) are calculated
 * from the Daily Rate via the configured multipliers, then normalized to a
 * "smart" whole-dollar price ending:
 *
 *   - Round upward to the lowest whole-dollar amount, not below the raw
 *     calculated price, whose final digit is one of the configured allowed
 *     endings.
 *   - Hundred-entry protection: a price does not move into a new
 *     hundred-dollar band until the raw amount is at least the configured
 *     threshold into that hundred. Below the threshold — and when ordinary
 *     upward rounding would cross into the next hundred — the price falls
 *     back to the highest allowed ending in the current (or previous) band.
 *   - Below the first hundred, where no previous band exists, the lowest
 *     valid upward ending is used instead.
 *
 * Smart rounding is active only when at least one allowed ending is
 * configured; with none configured, raw multiplier prices are used
 * unchanged (the pre-existing behavior). Nothing here hard-codes the
 * endings or the threshold — both come from settings.
 *
 * All band/threshold logic runs on integer cents so no floating-point
 * comparison can flip a boundary case.
 */
class RentalPriceHelper
{
    /** Setting names (setting_type 'Price Rate Multiplier Settings'). */
    public const ENDINGS_SETTING = 'allowed_price_endings';
    public const THRESHOLD_SETTING = 'hundred_entry_threshold';

    /** Derived rental periods (rental_{period} columns, {period}_multiplier settings). */
    public const PERIODS = ['weekend', 'weekly', 'monthly'];

    /**
     * Authoritative save-time resolution of the derived rental prices.
     *
     * The product form tracks a per-period source state
     * ({period}_price_source = auto|manual). For a field the browser marks
     * 'auto', the submitted amount is DISCARDED and recalculated here from
     * the submitted Daily Rate and the current global settings — a
     * manipulated or stale browser value can never persist as an
     * auto-calculated price. Anything else (manual, missing on legacy
     * forms, or an auto claim that cannot be honored because the multiplier
     * or Daily Rate is blank) preserves the submitted value verbatim, which
     * is what keeps manual overrides and older workflows intact.
     *
     * Returns the three rental_{period} keys ready to merge into the
     * product payload. Callers gate on product_type Rental.
     */
    public static function resolveDerivedPrices(array $validated): array
    {
        $settings = ConfigurationHelper::getSettings('Price Rate Multiplier Settings');
        $endings = self::parseEndings($settings[self::ENDINGS_SETTING] ?? null);
        $threshold = self::parseThreshold($settings[self::THRESHOLD_SETTING] ?? null);

        $resolved = [];

        foreach (self::PERIODS as $period) {
            $resolved["rental_{$period}"] = $validated["rental_{$period}"] ?? null;

            if (($validated["{$period}_price_source"] ?? null) !== 'auto') {
                continue;
            }

            $calculated = self::derivedPrice(
                $validated['rental_daily'] ?? null,
                $settings["{$period}_multiplier"] ?? null,
                $endings,
                $threshold,
            );

            if ($calculated !== null) {
                $resolved["rental_{$period}"] = $calculated;
            }
        }

        return $resolved;
    }

    /**
     * Parse the stored comma-separated endings ("4,7") into a sorted,
     * de-duplicated list of digits. Invalid entries are ignored.
     *
     * @return int[]
     */
    public static function parseEndings(?string $csv): array
    {
        if ($csv === null || trim($csv) === '') {
            return [];
        }

        $digits = [];
        foreach (explode(',', $csv) as $part) {
            $part = trim($part);
            if ($part !== '' && ctype_digit($part) && strlen($part) === 1) {
                $digits[(int) $part] = true;
            }
        }

        $endings = array_keys($digits);
        sort($endings);

        return $endings;
    }

    /**
     * Threshold in whole dollars; blank/invalid values fall back to 0
     * (no hundred-entry protection).
     */
    public static function parseThreshold(mixed $value): int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return 0;
        }

        return max(0, (int) $value);
    }

    /**
     * Derive one rental-period price from the Daily Rate.
     *
     * Returns a "0.00"-formatted string, or null when auto-calculation does
     * not apply (missing/zero daily rate or multiplier — matching the
     * existing form behavior of leaving the field untouched/blank).
     */
    public static function derivedPrice(mixed $daily, mixed $multiplier, array $endings, int $thresholdDollars): ?string
    {
        if (! is_numeric($daily) || ! is_numeric($multiplier)) {
            return null;
        }

        if ((float) $daily <= 0 || (float) $multiplier <= 0) {
            return null;
        }

        $dailyCents = self::toCents((string) $daily);
        $rawCents = (int) round($dailyCents * (float) $multiplier);

        if ($endings === []) {
            // Smart rounding inactive: raw multiplier price, unchanged behavior.
            return number_format($rawCents / 100, 2, '.', '');
        }

        $dollars = self::smartRoundDollars($rawCents, $endings, $thresholdDollars);

        return number_format($dollars, 2, '.', '');
    }

    /**
     * Core smart-rounding rule. Takes the raw calculated price in cents and
     * returns the normalized whole-dollar price.
     *
     * @param int[] $endings allowed final digits (0–9), at least one
     */
    public static function smartRoundDollars(int $rawCents, array $endings, int $thresholdDollars): int
    {
        $hundredCents = intdiv($rawCents, 10000) * 10000;
        $offsetCents = $rawCents - $hundredCents;
        $hundredDollars = intdiv($hundredCents, 100);

        // Hundred-entry protection: stay in the previous band until the raw
        // price is at least the threshold into the new hundred. Only applies
        // from $100 up — below that there is no previous band.
        if ($hundredCents >= 10000 && $offsetCents < $thresholdDollars * 100) {
            return self::highestEndingBelow($hundredDollars, $endings);
        }

        // Ordinary upward normalization to the lowest allowed ending.
        $ceilDollars = intdiv($rawCents + 99, 100);
        $up = self::lowestEndingAtOrAbove($ceilDollars, $endings);

        if ($up < $hundredDollars + 100) {
            return $up;
        }

        // Upward rounding would cross into the next hundred: fall back to the
        // highest allowed ending at or below the raw price within this band.
        $floorDollars = intdiv($rawCents, 100);
        for ($d = $floorDollars; $d >= $hundredDollars; $d--) {
            if (in_array($d % 10, $endings, true)) {
                return $d;
            }
        }

        // Unreachable with valid inputs (crossing implies raw is above the
        // band's highest ending), but never return an invalid price.
        return $up;
    }

    /** Highest whole-dollar amount strictly below $limit ending in an allowed digit. */
    private static function highestEndingBelow(int $limit, array $endings): int
    {
        for ($d = $limit - 1; $d >= 0; $d--) {
            if (in_array($d % 10, $endings, true)) {
                return $d;
            }
        }

        // No valid price below the limit (e.g. band 0–99 exhausted):
        // use the lowest valid upward ending instead of an invalid price.
        return self::lowestEndingAtOrAbove($limit, $endings);
    }

    /** Lowest whole-dollar amount >= $from ending in an allowed digit. */
    private static function lowestEndingAtOrAbove(int $from, array $endings): int
    {
        for ($d = max(0, $from); ; $d++) {
            if (in_array($d % 10, $endings, true)) {
                return $d;
            }
        }
    }

    /** Decimal-safe string-to-cents conversion (no float multiplication). */
    private static function toCents(string $value): int
    {
        $value = trim($value);
        $negative = str_starts_with($value, '-');
        $value = ltrim($value, '+-');

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $whole = $whole === '' ? '0' : $whole;

        // Round half-up on the third fractional digit if present.
        $fraction = str_pad($fraction, 3, '0');
        $cents = ((int) $whole) * 100 + (int) substr($fraction, 0, 2);
        if ((int) $fraction[2] >= 5) {
            $cents++;
        }

        return $negative ? -$cents : $cents;
    }
}
