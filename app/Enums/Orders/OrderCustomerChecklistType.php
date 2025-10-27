<?php

namespace App\Enums\Orders;

enum OrderCustomerChecklistType : string
{
    case ChecklistDelivery = 'checklist_delivery';
    case ChecklistReturn = 'checklist_return';
    case ChecklistRemoved = 'checklist_removed';

    public function label(): string
    {
        return match($this) {
            self::ChecklistDelivery => 'Customer Checklist Delivery',
            self::ChecklistReturn => 'Customer Checklist Return',
            self::ChecklistRemoved => 'Customer Checklist Removed',
        };
    }

    public function isChecklistDelivery(): bool
    {
        return $this === self::ChecklistDelivery;
    }

    public function isChecklistReturn(): bool
    {
        return $this === self::ChecklistReturn;
    }
}
