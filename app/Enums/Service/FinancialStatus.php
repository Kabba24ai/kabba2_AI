<?php

namespace App\Enums\Service;

enum FinancialStatus: string
{
    case NotBillable             = 'not_billable';
    case ReadyToBill             = 'ready_to_bill';
    case ChargeCreated           = 'charge_created';
    case PartiallyPaid           = 'partially_paid';
    case Paid                    = 'paid';
    case WrittenOff              = 'written_off';
    case WarrantyNotSubmitted    = 'warranty_not_submitted';
    case WarrantySubmitted       = 'warranty_submitted';
    case WarrantyWaitingApproval = 'warranty_waiting_approval';
    case WarrantyApproved        = 'warranty_approved';
    case WarrantyDenied          = 'warranty_denied';
    case WarrantyPaidByOem       = 'warranty_paid_by_oem';
    case InternalClosed          = 'internal_closed';

    public function label(): string
    {
        return match ($this) {
            self::NotBillable             => 'Not Billable',
            self::ReadyToBill             => 'Ready to Bill',
            self::ChargeCreated           => 'Charge Created',
            self::PartiallyPaid           => 'Partially Paid',
            self::Paid                    => 'Paid',
            self::WrittenOff              => 'Written Off',
            self::WarrantyNotSubmitted    => 'Warranty Not Submitted',
            self::WarrantySubmitted       => 'Warranty Submitted',
            self::WarrantyWaitingApproval => 'Warranty Waiting Approval',
            self::WarrantyApproved        => 'Warranty Approved',
            self::WarrantyDenied          => 'Warranty Denied',
            self::WarrantyPaidByOem       => 'Warranty Paid by OEM',
            self::InternalClosed          => 'Internal Closed',
        };
    }

    /** Warranty claim states considered pending (Financial summary card). */
    public static function warrantyPending(): array
    {
        return [
            self::WarrantyNotSubmitted->value,
            self::WarrantySubmitted->value,
            self::WarrantyWaitingApproval->value,
        ];
    }

    /** Charges created but not fully collected (Outstanding Payments). */
    public static function outstanding(): array
    {
        return [
            self::ChargeCreated->value,
            self::PartiallyPaid->value,
        ];
    }
}
