<?php

namespace App\Enums\GiftCards;

/**
 * A gift card's lifecycle state.
 *
 * Status governs whether a card may be PRESENTED for redemption. It never
 * governs how much it is worth — that is the ledger's answer alone. A card can
 * be Active with a zero balance (fully spent but not yet transitioned) and the
 * redemption still fails, because the balance check is separate and
 * authoritative.
 */
enum GiftCardStatus: string
{
    /** Created, not yet funded. Cannot be redeemed. */
    case Draft = 'draft';

    case Active = 'active';
    case PartiallyRedeemed = 'partially_redeemed';
    case FullyRedeemed = 'fully_redeemed';

    /** Temporarily blocked — suspected fraud, dispute. Reversible. */
    case Suspended = 'suspended';

    /** Permanently void. Remaining value written off in the ledger. */
    case Cancelled = 'cancelled';

    /** Superseded by a replacement card; value transferred out. */
    case Replaced = 'replaced';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Active => 'Active',
            self::PartiallyRedeemed => 'Partially Redeemed',
            self::FullyRedeemed => 'Fully Redeemed',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
            self::Replaced => 'Replaced',
        };
    }

    /**
     * May a card in this state be presented for payment?
     *
     * A necessary condition, never a sufficient one — the balance is checked
     * separately, under a lock. `FullyRedeemed` is excluded here as well as by
     * balance so that a card whose cached status has drifted is still refused.
     */
    public function isRedeemable(): bool
    {
        return match ($this) {
            self::Active, self::PartiallyRedeemed => true,
            self::Draft, self::FullyRedeemed,
            self::Suspended, self::Cancelled, self::Replaced => false,
        };
    }

    /** Terminal states — no further value movement is expected. */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::FullyRedeemed, self::Cancelled, self::Replaced => true,
            default => false,
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-100 text-emerald-800',
            self::PartiallyRedeemed => 'bg-sky-100 text-sky-800',
            self::FullyRedeemed => 'bg-gray-200 text-gray-700',
            self::Draft => 'bg-amber-100 text-amber-800',
            self::Suspended => 'bg-orange-100 text-orange-800',
            self::Cancelled => 'bg-red-100 text-red-800',
            self::Replaced => 'bg-indigo-100 text-indigo-800',
        };
    }
}
