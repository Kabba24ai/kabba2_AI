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

    /**
     * A COD ("Pay on Delivery") placeholder row — created at checkout with
     * amount = the order's full grand_total — whose order was instead paid
     * off in full through a different payment method. Deliberately NOT
     * Paid: OrderPayment::scopeSettled() (which feeds Order::total_paid /
     * is_paid / refund-balance math) sums by status, so marking this row
     * Paid would double-count a full grand_total of phantom revenue
     * against the order alongside the real settling payment. System-set
     * only — never selectable in a payment-entry form.
     */
    case Superseded = 'Superseded';

    public function label(): string
    {
       return match($this) {
           self::Pending => 'Pending',
           self::Paid => 'Paid in Full',
           self::Account => 'Account',
           self::PartialRefund => 'Partially Refunded',
           self::Refund => 'Refunded',
           self::Failed => 'Failed',
           self::PartialPayment => 'Partially Paid',
           self::InvoiceCard => 'Paid by CC on File',
           self::InvoiceCash => 'Paid by Cash',
           self::InvoiceOnline => 'Paid by Direct Bank',
           self::InvoiceCheque => 'Paid by Check',
           self::InvoiceOther => 'Other Invoice Payment',
           self::Voided => 'Voided',
           self::Superseded => 'Superseded (Paid via Other Method)',
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

    /** Paid outright, or paid through the legacy Invoice workflow — either way, money is in. */
    public function isSettled(): bool
    {
        return $this->isPaid() || $this->isInvoice();
    }

    /**
     * The Invoice* cases conflate status with method (e.g. "Invoice Cash"
     * means "paid, via cash, through the Invoice flow"). This recovers the
     * method half so callers can display Status and Method separately
     * instead of the fused legacy label — null for cases that carry no
     * method information of their own.
     */
    public function impliedMethod(): ?OrderPaymentMethod
    {
        return match ($this) {
            self::InvoiceCard => OrderPaymentMethod::Card,
            self::InvoiceCash => OrderPaymentMethod::Cash,
            self::InvoiceOnline => OrderPaymentMethod::Online,
            self::InvoiceCheque => OrderPaymentMethod::Cheque,
            self::InvoiceOther => OrderPaymentMethod::Other,
            default => null,
        };
    }

    /**
     * The seven statuses in the canonical vocabulary — excludes Account
     * (an Accounts Receivable workflow marker) and the legacy Invoice*
     * cases (status+method fused; use impliedMethod() to split them
     * instead of offering them as their own status). Use this — not
     * self::cases() — to build any status filter dropdown.
     */
    public static function canonical(): array
    {
        return [self::Pending, self::Paid, self::PartialPayment, self::PartialRefund, self::Refund, self::Voided, self::Failed];
    }

    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }

}
