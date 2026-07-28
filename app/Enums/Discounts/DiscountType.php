<?php

namespace App\Enums\Discounts;

/**
 * Product-discount families. All reduce pre-tax product value; they differ only
 * in funding + balance effect. Only store_credit is operationally exposed in
 * Phase 1 — goodwill and general are structurally supported (so the engine and
 * reporting can represent them) but are NOT accepted by Phase 1 endpoints.
 */
enum DiscountType: string
{
    case StoreCredit = 'store_credit'; // funded by the customer's Store Credit balance
    case Goodwill = 'goodwill';        // Kabba discretionary concession (deferred)
    case General = 'general';          // Kabba commercial % discount (deferred)

    public function label(): string
    {
        return match ($this) {
            self::StoreCredit => 'Store Credit',
            self::Goodwill    => 'Goodwill',
            self::General     => 'General Discount',
        };
    }

    /** Whether this type may be applied through Phase 1 operational endpoints. */
    public function isOperationalInPhase1(): bool
    {
        return $this === self::StoreCredit;
    }

    /** Store Credit is the only type that draws down the Store Credit balance. */
    public function drawsDownStoreCredit(): bool
    {
        return $this === self::StoreCredit;
    }

    public function defaultCalculationType(): DiscountCalculationType
    {
        return match ($this) {
            self::StoreCredit, self::Goodwill => DiscountCalculationType::FixedAmount,
            self::General                     => DiscountCalculationType::Percentage,
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::StoreCredit => 'bg-emerald-100 text-emerald-800',
            self::Goodwill    => 'bg-amber-100 text-amber-800',
            self::General     => 'bg-indigo-100 text-indigo-800',
        };
    }
}
