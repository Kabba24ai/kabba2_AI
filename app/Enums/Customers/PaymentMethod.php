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
    /**
     * @deprecated Store Credit is NO LONGER a payment/tender. It is a PRE-TAX
     * product discount funded from the customer's Store Credit balance, applied
     * only through the canonical discount engine. Excluded from options()/
     * canonical()/every dropdown/every validation rule so no new Store Credit
     * payment can be selected or submitted. The case + label() are kept ONLY so
     * legacy stored payment rows (historical tender activity) still render —
     * never so Store Credit can be newly selected as a tender again.
     */
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

    /**
     * The approved SELECTABLE tender methods, excluding BankTransfer AND
     * StoreCredit (both @deprecated — see the case notes). Store Credit is a
     * discount now, not a tender, so it never appears in a payment dropdown or
     * a payment validation rule built from this list.
     */
    public static function canonical(): array
    {
        return array_values(array_filter(
            self::cases(),
            fn (self $case) => !in_array($case, [self::BankTransfer, self::StoreCredit], true),
        ));
    }

    /**
     * Valid REFUND destinations: the canonical collection methods PLUS Store
     * Credit. Refund-to-Store-Credit ISSUES balance (a grant + append-only
     * issuance history) — it is NOT a new tender/collection — so Store Credit
     * is admissible here even though it was removed from canonical() (the
     * collection list). Use this ONLY for refund-destination validation, never
     * for a payment/collection dropdown.
     */
    public static function refundDestinations(): array
    {
        return [...self::canonical(), self::StoreCredit];
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
