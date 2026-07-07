<?php

namespace App\Enums\Orders;

/**
 * Operational reason codes for refund / void actions. Selected in the
 * "Processed By" section of the refund and void modals; the code and its
 * label are both stored on the order_payments row so the audit trail is
 * readable even if labels change later.
 */
enum ProcessedReason: string
{
    case CustomerCancellation       = 'customer_cancellation';
    case OverpaymentCorrection      = 'overpayment_correction';
    case DepositReturn              = 'deposit_return';
    case BillingError               = 'billing_error';
    case EquipmentUnavailable       = 'equipment_unavailable';
    case ManagerApprovedAdjustment  = 'manager_approved_adjustment';
    case DuplicatePaymentOrder      = 'duplicate_payment_order';
    case Other                      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CustomerCancellation      => 'Customer cancellation',
            self::OverpaymentCorrection     => 'Overpayment correction',
            self::DepositReturn             => 'Deposit return',
            self::BillingError              => 'Billing error',
            self::EquipmentUnavailable      => 'Equipment unavailable',
            self::ManagerApprovedAdjustment => 'Manager approved adjustment',
            self::DuplicatePaymentOrder     => 'Duplicate payment/order',
            self::Other                     => 'Other',
        };
    }

    /** value => label pairs for select fields. */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->all();
    }
}
