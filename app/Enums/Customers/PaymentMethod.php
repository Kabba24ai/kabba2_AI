<?php

namespace App\Enums\Customers;

enum PaymentMethod: string
{
    case CreditCard = 'CreditCard';
    case Cash = 'Cash';
    case Cheque = 'Cheque';
    case BankTransfer = 'BankTransfer';
    case Other = 'Other';

    public static function options(): array
    {
        return [
            '' => 'Select payment method',
            self::CreditCard->value => 'Credit / Debit Card',
            self::Cash->value => 'Cash',
            self::Cheque->value => 'Check',
            self::BankTransfer->value => 'Bank Transfer',
            self::Other->value => 'Other',
        ];
    }

    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Credit / Debit Card',
            self::Cash => 'Cash',
            self::Cheque => 'Check',
            self::BankTransfer => 'Bank Transfer',
            self::Other => 'Other',
        };
    }
}
