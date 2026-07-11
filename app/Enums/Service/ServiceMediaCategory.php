<?php

namespace App\Enums\Service;

enum ServiceMediaCategory: string
{
    case ComplaintEvidence           = 'complaint_evidence';
    case BeforeRepair                = 'before_repair';
    case DuringRepair                = 'during_repair';
    case AfterRepair                 = 'after_repair';
    case WarrantyDocumentation       = 'warranty_documentation';
    case CustomerDamageDocumentation = 'customer_damage_documentation';
    case General                     = 'general';

    public function label(): string
    {
        return match ($this) {
            self::ComplaintEvidence           => 'Complaint Evidence',
            self::BeforeRepair                => 'Before Repair',
            self::DuringRepair                => 'During Repair',
            self::AfterRepair                 => 'After Repair',
            self::WarrantyDocumentation       => 'Warranty Documentation',
            self::CustomerDamageDocumentation => 'Customer Damage Documentation',
            self::General                     => 'General',
        };
    }
}
