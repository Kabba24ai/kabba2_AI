<?php

namespace App\Enums\GiftCards;

/**
 * Where a gift card's value came from.
 *
 * Both classes spend identically at the register: they are tender, applied
 * after tax, and they never reduce an order's taxable basis. They are never
 * the same in the accounts, and the difference is not cosmetic:
 *
 *   Purchased — a customer handed over real money. The business now owes them
 *               goods or services. That obligation is a LIABILITY.
 *   Granted   — the business gave value away. No cash ever existed. That is
 *               MERCHANT-FUNDED PROMOTIONAL VALUE, an expense, not a debt.
 *
 * Summing the two into one "gift cards outstanding" figure would present money
 * the business owes and money it chose to give away as the same obligation.
 * {@see App\Services\Reports\SalesReportEngineV2} therefore reports them as
 * separate KPIs and never adds them together.
 */
enum GiftCardIssuanceClass: string
{
    case Purchased = 'purchased';
    case Granted = 'granted';

    public function label(): string
    {
        return match ($this) {
            self::Purchased => 'Purchased',
            self::Granted => 'Granted',
        };
    }

    /**
     * Did real money change hands at issuance?
     *
     * The single question that decides whether redemption draws down a
     * liability the business owes or promotional value it gave away — and
     * whether a funding payment must exist at all.
     */
    public function createsLiability(): bool
    {
        return $this === self::Purchased;
    }

    /** Granted value is merchant-funded: an expense, never a debt. */
    public function isPromotional(): bool
    {
        return $this === self::Granted;
    }

    public function transactionType(): GiftCardTransactionType
    {
        return match ($this) {
            self::Purchased => GiftCardTransactionType::IssuancePurchased,
            self::Granted => GiftCardTransactionType::IssuanceGranted,
        };
    }
}
