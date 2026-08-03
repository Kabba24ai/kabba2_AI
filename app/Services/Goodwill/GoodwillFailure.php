<?php

namespace App\Services\Goodwill;

/**
 * Every way a Goodwill operation can be refused, as a typed value.
 *
 * WHY AN ENUM RATHER THAN STRINGS. A caller has to be able to distinguish
 * "the operator lacks authority" from "the order moved since the preview" from
 * "this cannot be closed exactly" — they need different responses, different
 * HTTP statuses and different operator guidance. String matching on a message
 * breaks the first time the wording is improved.
 *
 * The messages are written for the person standing at the counter with a
 * customer in front of them: what happened, and what to do about it. None of
 * them exposes an internal identifier, a SQL error or a stack position.
 */
enum GoodwillFailure: string
{
    case Unauthorized = 'unauthorized';
    case ApproverUnauthorized = 'approver_unauthorized';
    case NoteRequired = 'note_required';
    case NoSettledPayment = 'no_settled_payment';
    case AlreadyPaidInFull = 'already_paid_in_full';
    case PostedToAccount = 'posted_to_account';
    case Invoiced = 'invoiced';
    case ActiveAdjustmentExists = 'active_adjustment_exists';
    case NoMerchandiseLines = 'no_merchandise_lines';
    case NotDiscountable = 'not_discountable';
    case StaleState = 'stale_state';
    case Unreachable = 'unreachable';
    case ResidualOutOfBounds = 'residual_out_of_bounds';
    case AlreadyReversed = 'already_reversed';
    case ReversalBlocked = 'reversal_blocked';
    case Contended = 'contended';

    public function message(): string
    {
        return match ($this) {
            self::Unauthorized => 'You are not authorized to apply or reverse a Goodwill adjustment.',

            self::ApproverUnauthorized => 'The selected manager is not authorized to approve a Goodwill '
                .'adjustment. Choose a manager who holds Goodwill approval.',

            self::NoteRequired => 'A Goodwill adjustment recorded as "Other" requires a written explanation.',

            self::NoSettledPayment => 'Record the customer\'s payment first. Goodwill closes the gap between '
                .'what was collected and the order total — it is not a payment itself.',

            self::AlreadyPaidInFull => 'This order has no remaining balance, so there is nothing for a Goodwill '
                .'adjustment to close.',

            self::PostedToAccount => 'This order has been posted to the customer\'s Credit Account. Adjust the '
                .'account balance instead.',

            self::Invoiced => 'This order is on an invoice. Adjust the invoice instead — reducing the order '
                .'now would leave the two disagreeing.',

            self::ActiveAdjustmentExists => 'A Goodwill adjustment is already applied to this order. Reverse it '
                .'before applying another.',

            self::NoMerchandiseLines => 'This order has no merchandise lines for a pre-tax concession to reduce.',

            self::NotDiscountable => 'This order cannot currently take a pre-tax adjustment.',

            self::StaleState => 'This order changed since the Goodwill amount was calculated. Reload the order '
                .'and try again — nothing was applied.',

            self::Unreachable => 'Goodwill cannot close this balance. The amount still outstanding is made up of '
                .'fees and other non-merchandise charges, which a merchandise concession cannot reduce.',

            self::ResidualOutOfBounds => 'This order cannot be closed exactly within the permitted rounding '
                .'tolerance. Reload the order and try again — nothing was applied.',

            self::AlreadyReversed => 'This Goodwill adjustment has already been reversed.',

            self::ReversalBlocked => 'This Goodwill adjustment can no longer be reversed: the order has since been '
                .'refunded, invoiced, or posted to the Credit Account.',

            self::Contended => 'Another update to this order is in progress. Nothing was applied — try again in a '
                .'moment.',
        };
    }

    /** The HTTP status a controller should return. Kept here so it cannot drift per endpoint. */
    public function httpStatus(): int
    {
        return match ($this) {
            self::Unauthorized, self::ApproverUnauthorized => 403,
            self::StaleState, self::ActiveAdjustmentExists, self::Contended => 409,
            default => 422,
        };
    }
}
