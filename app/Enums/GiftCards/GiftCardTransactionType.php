<?php

namespace App\Enums\GiftCards;

/**
 * What a gift-card ledger row records.
 *
 * ── SIGN LIVES ON THE AMOUNT, NOT ON THE TYPE ─────────────────────────────
 *
 * `gift_card_transactions.amount` is signed, and a balance is SUM(amount). This
 * enum therefore never maps a type to a direction for the purpose of computing
 * a balance — {@see self::isCredit()} exists only so a service can VALIDATE
 * that a caller passed a sign consistent with the type it asked for. Deriving
 * direction from type would give a new case the chance to be added with the
 * wrong convention and silently invert a balance.
 */
enum GiftCardTransactionType: string
{
    /** Purchased issuance — real money received, liability created. */
    case IssuancePurchased = 'issuance_purchased';

    /** Granted issuance — no money, promotional value created. */
    case IssuanceGranted = 'issuance_granted';

    /** Value spent against an order. Always accompanied by an OrderPayment. */
    case Redemption = 'redemption';

    /** A redemption undone — voided payment, cancelled order. */
    case RedemptionReversal = 'redemption_reversal';

    /** A refund routed back to the card it was paid with. */
    case RefundToCard = 'refund_to_card';

    case AdjustmentIncrease = 'adjustment_increase';
    case AdjustmentDecrease = 'adjustment_decrease';

    /** Remaining value written off when a card is cancelled. */
    case Cancellation = 'cancellation';

    /** Value moved to a replacement card (lost/damaged). */
    case ReplacementTransfer = 'replacement_transfer';

    public function label(): string
    {
        return match ($this) {
            self::IssuancePurchased => 'Purchased',
            self::IssuanceGranted => 'Granted',
            self::Redemption => 'Redeemed',
            self::RedemptionReversal => 'Redemption Reversed',
            self::RefundToCard => 'Refunded to Card',
            self::AdjustmentIncrease => 'Adjustment (Increase)',
            self::AdjustmentDecrease => 'Adjustment (Decrease)',
            self::Cancellation => 'Cancelled',
            self::ReplacementTransfer => 'Transferred to Replacement',
        };
    }

    /**
     * Should this type carry a positive amount?
     *
     * Used to reject a caller that asks for a redemption with a positive
     * amount, or a refund with a negative one — mistakes that would otherwise
     * produce a perfectly valid-looking row that moves value the wrong way.
     */
    public function isCredit(): bool
    {
        return match ($this) {
            self::IssuancePurchased,
            self::IssuanceGranted,
            self::RedemptionReversal,
            self::RefundToCard,
            self::AdjustmentIncrease => true,

            self::Redemption,
            self::AdjustmentDecrease,
            self::Cancellation,
            self::ReplacementTransfer => false,
        };
    }

    /** Does this row bring real external cash into the business? */
    public function bringsExternalCash(): bool
    {
        // ONLY purchased issuance. A redemption moves no money — the cash
        // arrived when the card was funded, and counting it again is the
        // double-count this whole feature is built to prevent.
        return $this === self::IssuancePurchased;
    }

    /** Types that open or close purchased-card liability. */
    public function affectsPurchasedLiability(): bool
    {
        return $this !== self::IssuanceGranted;
    }
}
