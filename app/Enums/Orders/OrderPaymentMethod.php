<?php

namespace App\Enums\Orders;

enum OrderPaymentMethod : string
{
    case COD = 'COD';
    case Account = 'Account';
    case Card = 'Card';
    case Cash = 'Cash';
    /**
     * @deprecated Not part of the approved canonical method list — this
     * system has never accepted or recorded Bank Transfer payments.
     * Excluded from canonical()/every dropdown/every validation rule. The
     * case and its label are kept only so a legacy stored row (if any is
     * ever found — none were found in the data searched at the time of
     * removal) can still render without throwing, never so it can be
     * newly selected again.
     */
    case Online = 'Online';
    case Cheque = 'Cheque';
    case Other = 'Other';
    case TapToPay = 'TapToPay';
    case StoreCredit = 'StoreCredit';
    case GiftCard = 'GiftCard';
    case ZelleVenmo = 'ZelleVenmo';

    /**
     * Cash means cash: this label is shown wherever a payment method is
     * displayed, so it must say only what the employee actually selected —
     * never a channel, location, or workflow guess layered on top.
     */
    public function label(): string
    {
        return match ($this) {
            self::COD => 'Pay on Delivery',
            self::Account => 'Account',
            self::Card => 'Credit / Debit Card',
            self::Cash => 'Cash',
            self::Online => 'Bank Transfer',
            self::Cheque => 'Check',
            self::Other => 'Other',
            self::TapToPay => 'Tap to Pay',
            self::StoreCredit => 'Store Credit',
            self::GiftCard => 'Gift Card',
            self::ZelleVenmo => 'Zelle / Venmo',
        };
    }

    /**
     * The eight approved payment methods a customer actually paid with.
     * Excludes COD (a payment-terms placeholder set at order creation,
     * never itself a completed method), Account (an Accounts Receivable
     * workflow marker, not a way funds were transferred), and Online/Bank
     * Transfer (never an accepted payment method in this system — see the
     * @deprecated note on the case itself). Use this — not self::cases()
     * — to build any payment-method selection dropdown or validation rule.
     * For a FILTER dropdown (which must also isolate COD and on-Account
     * orders), use filterOptions().
     */
    public static function canonical(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => !in_array($case, [self::COD, self::Account, self::Online], true),
        ));
    }

    /**
     * Method options for a FILTER dropdown: the approved canonical() methods
     * plus the two operational markers a filter must be able to isolate —
     * COD (pay-on-delivery orders) and Account (orders on the customer's
     * credit account). The one canonical provider every payment-method
     * filter should build from, replacing hand-rolled array_merge([COD], …)
     * lists. Online/Bank Transfer stays out (retired, never selectable).
     */
    public static function filterOptions(): array
    {
        return [self::COD, self::Account, ...self::canonical()];
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}
