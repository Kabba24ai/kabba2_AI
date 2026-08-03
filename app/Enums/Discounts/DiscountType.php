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

    /**
     * How this adjustment is named on a CUSTOMER-FACING document.
     *
     * Deliberately more explicit than {@see self::label()}: a receipt has to
     * tell the customer both WHAT was applied and THAT it reduced the taxable
     * merchandise basis rather than being a payment. "Store Credit" alone
     * leaves a reader unable to tell a pre-tax concession from tender.
     *
     * Gift Card is intentionally absent from this enum. It is PURCHASED VALUE
     * — a form of tender — not a pre-tax adjustment: it does not reduce the
     * taxable merchandise basis and must never be grouped with the types
     * below. Adding it here would misclassify it, which is why the suffix is
     * attached per-case rather than appended to every label.
     */
    public function receiptLabel(): string
    {
        return match ($this) {
            self::StoreCredit => 'Store Credit - Pre-Tax',
            self::Goodwill    => 'Goodwill - Pre-Tax',
            self::General     => 'General Discount - Pre-Tax',
        };
    }

    /**
     * Does this type reduce the taxable merchandise basis?
     *
     * True for every case here by construction — the enum models pre-tax
     * product discounts. The method exists so a future tender type (Gift Card)
     * cannot be added to this enum without the question being asked
     * explicitly.
     */
    public function reducesTaxableBasis(): bool
    {
        return true;
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
