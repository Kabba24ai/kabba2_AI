<?php

namespace App\Services\GiftCards;

/**
 * Every way a gift-card operation can be refused, as a typed value.
 *
 * Typed rather than a bare message so a caller can branch on the reason — a
 * balance shortfall is retryable with a smaller amount, a cancelled card is
 * not — and so the operator-facing wording lives in one place instead of being
 * reinvented at each call site. The same shape as
 * {@see App\Services\Goodwill\GoodwillFailure}.
 */
enum GiftCardFailure: string
{
    case CardNotFound = 'card_not_found';
    case CardNotRedeemable = 'card_not_redeemable';
    case InsufficientBalance = 'insufficient_balance';
    case ExceedsOrderBalance = 'exceeds_order_balance';
    case AmountNotPositive = 'amount_not_positive';
    case OrderNotPayable = 'order_not_payable';
    case InvalidPin = 'invalid_pin';
    case FundingMethodInvalid = 'funding_method_invalid';
    case GrantReasonRequired = 'grant_reason_required';
    case Contended = 'contended';
    case NotAuthorized = 'not_authorized';
    case ReasonRequired = 'reason_required';
    case NothingToRefund = 'nothing_to_refund';
    case RefundExceedsRedeemed = 'refund_exceeds_redeemed';
    case AlreadyInThatState = 'already_in_that_state';
    case CardNotReplaceable = 'card_not_replaceable';

    public function message(): string
    {
        return match ($this) {
            self::CardNotFound => 'No gift card was found with that number.',
            self::CardNotRedeemable => 'This gift card cannot be used — check its status.',
            self::InsufficientBalance => 'The gift card does not have enough remaining value.',
            self::ExceedsOrderBalance => 'The amount is more than this order still owes.',
            self::AmountNotPositive => 'Enter an amount greater than zero.',
            self::OrderNotPayable => 'This order cannot accept a payment right now.',
            self::InvalidPin => 'That PIN does not match this gift card.',
            self::FundingMethodInvalid => 'A gift card cannot be funded with a gift card.',
            self::GrantReasonRequired => 'A granted gift card needs a reason.',
            self::Contended => 'This gift card is being used somewhere else. Try again in a moment.',
            self::NotAuthorized => 'You are not authorized to perform this gift card operation.',
            self::ReasonRequired => 'This operation requires a reason.',
            self::NothingToRefund => 'This payment did not draw on a gift card.',
            self::RefundExceedsRedeemed => 'That is more than this order took from the card.',
            self::AlreadyInThatState => 'This gift card is already in that state.',
            self::CardNotReplaceable => 'This gift card cannot be replaced — check its status.',
        };
    }

    /** Would the same request succeed later, unchanged? */
    public function isTransient(): bool
    {
        return $this === self::Contended;
    }
}
