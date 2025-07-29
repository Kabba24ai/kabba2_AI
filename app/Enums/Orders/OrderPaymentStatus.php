<?php

namespace App\Enums\Orders;

enum OrderPaymentStatus : string
{
    case Pending = 'Pending';
    case Paid = 'Paid';
    case Account = 'Account';
    case PartialRefund = 'Partial Refund';
    case Refund = 'Refunded';
    case Failed = 'Failed';


    public function label(): string
    {
       return match($this) {
           self::Pending => 'Pending',
           self::Paid => 'Paid',
           self::Account => 'Account',
           self::PartialRefund => 'Partial Refund',
           self::Refund => 'Refunded',
           self::Failed => 'Failed',
       };
    }

    public function isPaid(): bool
    {
        return $this === self::Paid;
    }

    public function isFailed(): bool
    {
        return $this === self::Failed;
    }

}
