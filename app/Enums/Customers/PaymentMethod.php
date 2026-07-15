<?php

namespace App\Enums\Customers;

/**
 * Drives every payment-entry form (Receive Payment, Refund, CRM account
 * payments) — the source of truth for what an employee actually selects.
 * App\Enums\Orders\OrderPaymentMethod's canonical labels must match these
 * exactly; this enum is where the wording is authored.
 */
enum PaymentMethod: string
{
    case CreditCard = 'CreditCard';
    case Cash = 'Cash';
    case Cheque = 'Cheque';
    case TapToPay = 'TapToPay';
    case StoreCredit = 'StoreCredit';
    case GiftCard = 'GiftCard';
    case ZelleVenmo = 'ZelleVenmo';
    case Other = 'Other';
    /**
     * @deprecated Not part of the approved canonical method list — this
     * system has never accepted or recorded Bank Transfer payments.
     * Excluded from options()/canonical()/every dropdown/every validation
     * rule. Kept only so a legacy stored row (if any is ever found — none
     * were found in the data searched at the time of removal) can still
     * render without throwing, never so it can be newly selected again.
     */
    case BankTransfer = 'BankTransfer';

    /** The eight approved methods, excluding BankTransfer — see the @deprecated note on the case itself. */
    public static function canonical(): array
    {
        return array_values(array_filter(self::cases(), fn (self $case) => $case !== self::BankTransfer));
    }

    public static function options(): array
    {
        $options = ['' => 'Select payment method'];

        foreach (self::canonical() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit / Debit Card',
            self::Cash => 'Cash',
            self::Cheque => 'Check',
            self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
            self::TapToPay => 'Tap to Pay',
            self::StoreCredit => 'Store Credit',
            self::GiftCard => 'Gift Card',
            self::ZelleVenmo => 'Zelle / Venmo',
        };
    }
}
