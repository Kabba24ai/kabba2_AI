<?php

namespace App\Enums\Service;

enum ApprovalType: string
{
    case CustomerApproval           = 'customer_approval';
    case OemWarrantyApproval        = 'oem_warranty_approval';
    case InternalManagementApproval = 'internal_management_approval';
    case GoodwillManagementApproval = 'goodwill_management_approval';

    public function label(): string
    {
        return match ($this) {
            self::CustomerApproval           => 'Customer Approval',
            self::OemWarrantyApproval        => 'OEM Warranty Approval',
            self::InternalManagementApproval => 'Management Approval',
            self::GoodwillManagementApproval => 'Goodwill Management Approval',
        };
    }

    /** Short "Waiting on …" subject for workbench state labels. */
    public function waitingOnLabel(): string
    {
        return match ($this) {
            self::CustomerApproval           => 'Customer Approval',
            self::OemWarrantyApproval        => 'OEM Approval',
            self::InternalManagementApproval,
            self::GoodwillManagementApproval => 'Manager Approval',
        };
    }

    /** The approval path each responsibility decision requires. */
    public static function forDecision(ResponsibilityDecision $decision): ?self
    {
        return match ($decision) {
            ResponsibilityDecision::CustomerPay     => self::CustomerApproval,
            ResponsibilityDecision::OemWarranty     => self::OemWarrantyApproval,
            ResponsibilityDecision::InternalExpense => self::InternalManagementApproval,
            ResponsibilityDecision::Goodwill        => self::GoodwillManagementApproval,
            default                                 => null,
        };
    }
}
