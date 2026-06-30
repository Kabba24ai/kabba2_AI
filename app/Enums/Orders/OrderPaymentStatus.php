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

    case InvoiceCard = 'Invoice Card';
    case InvoiceCash = 'Invoice Cash';
    case InvoiceOnline = 'Invoice Online';
    case InvoiceCheque = 'Invoice Cheque';
    case InvoiceOther = 'Invoice Other';

    case PartialPayment = 'Partial Payment';

    case Voided = 'Voided';

    public function label(): string
    {
       return match($this) {
           self::Pending => 'Pending',
           self::Paid => 'Paid',
           self::Account => 'Account',
           self::PartialRefund => 'Partial Refund',
           self::Refund => 'Refunded',
           self::Failed => 'Failed',
           self::PartialPayment => 'Partial',
           self::InvoiceCard => 'Paid by CC on File',
           self::InvoiceCash => 'Paid at Front Desk',
           self::InvoiceOnline => 'Paid by Direct Bank',
           self::InvoiceCheque => 'Paid by Check',
           self::InvoiceOther => 'Other Invoice Payment',
           self::Voided => 'Voided',
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

    public function isFullRefund(): bool
    {
        return $this === self::Refund;
    }

    public function isInvoice(): bool
    {
        return in_array($this, [
            self::InvoiceCard,
            self::InvoiceCash,
            self::InvoiceOnline,
            self::InvoiceCheque,
            self::InvoiceOther,
        ]);
    }


    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

}
